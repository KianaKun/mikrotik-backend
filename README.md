# MikroTik Backend

Backend API untuk manajemen token WiFi MikroTik berbasis Laravel 11.

---

## Tech Stack

- **Laravel 11**
- **MySQL**
- **Laravel Sanctum** — autentikasi API
- **RouterOS API PHP** — koneksi ke MikroTik
- **DomPDF** — export token ke PDF

---

## Instalasi

### 1. Clone & Install Dependencies

```bash
git clone https://github.com/KianaKun/mikrotik-backend.git
cd mikrotik-backend
composer install
```

### 2. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit file `.env`:

```env
APP_NAME="MikroTik Backend"
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mikrotik_backend
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=file

MIKROTIK_HOST=192.168.1.1
MIKROTIK_PORT=8728
MIKROTIK_USERNAME=admin
MIKROTIK_PASSWORD=
MIKROTIK_HOTSPOT_SERVER=hotspot1

TOKEN_COUNT_DEFAULT=80
TOKEN_DURATION_HOURS=6
```

### 3. Publish Sanctum

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 4. Migrasi & Seeder

```bash
php artisan migrate --seed
```

Akun admin default:
- **Username:** `admin`
- **Password:** `admin123`

> Segera ganti password setelah pertama login.

### 5. Jalankan Server

```bash
php artisan serve
```

---

## Struktur Project

```
mikrotik-backend/
├── app/
│   ├── Console/Commands/
│   │   └── GenerateTokens.php       # Artisan command generate token
│   ├── Http/Controllers/Api/
│   │   ├── AdminAuthController.php  # Login, logout, profile admin
│   │   ├── UserAuthController.php   # Login user via kode token
│   │   ├── TokenController.php      # CRUD & generate token
│   │   ├── SettingsController.php   # Konfigurasi aplikasi
│   │   └── MikroTikController.php   # Devices & kecepatan WiFi
│   ├── Models/
│   │   ├── Admin.php
│   │   ├── Token.php
│   │   └── Setting.php
│   └── Services/
│       ├── TokenService.php         # Logic generate & validasi token
│       └── MikroTikService.php      # Koneksi & operasi RouterOS API
├── config/
│   └── mikrotik.php                 # Konfigurasi koneksi MikroTik
├── database/
│   ├── migrations/                  # 3 tabel: admins, tokens, settings
│   └── seeders/                     # AdminSeeder, SettingsSeeder
├── resources/views/pdf/
│   └── tokens.blade.php             # Template PDF cetak token
└── routes/
    ├── api.php                      # Semua API endpoint
    └── console.php                  # Scheduler harian
```

---

## Scheduler

Token di-generate otomatis setiap hari jam **07:00 WIB**.

### Windows (Development)
Buat file `scheduler.ps1` di root project:

```powershell
while ($true) {
    Set-Location "D:\path\to\mikrotik-backend"
    php artisan schedule:run
    Start-Sleep -Seconds 60
}
```

Jalankan:
```powershell
powershell -ExecutionPolicy Bypass -File "D:\path\to\mikrotik-backend\scheduler.ps1"
```

### Linux (Production)
```bash
crontab -e
```

Tambahkan:
```
* * * * * cd /var/www/mikrotik-backend && php artisan schedule:run >> /dev/null 2>&1
```

### Generate Manual
```bash
php artisan tokens:generate
```

---

## API Reference

Base URL: `http://127.0.0.1:8000/api`

Semua endpoint admin membutuhkan header:
```
Authorization: Bearer {token}
```

---

### 🔐 Auth

#### `POST /admin/login`
Login admin. **Public.**

Request:
```json
{
    "username": "admin",
    "password": "admin123"
}
```

Response `200`:
```json
{
    "success": true,
    "data": {
        "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
        "admin": {
            "id": 1,
            "name": "Administrator",
            "username": "admin"
        }
    }
}
```

Response `422`:
```json
{
    "message": "Username atau password salah.",
    "errors": {
        "username": ["Username atau password salah."]
    }
}
```

---

#### `POST /admin/logout`
Logout admin. Requires Bearer token.

Response `200`:
```json
{
    "success": true,
    "message": "Logout berhasil."
}
```

---

#### `GET /admin/profile`
Lihat profil admin. Requires Bearer token.

Response `200`:
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Administrator",
        "username": "admin"
    }
}
```

---

#### `PUT /admin/profile`
Update profil admin. Requires Bearer token.

Request:
```json
{
    "name": "Admin Baru",
    "username": "admin",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

Response `200`:
```json
{
    "success": true,
    "message": "Profil berhasil diperbarui.",
    "data": {
        "id": 1,
        "name": "Admin Baru",
        "username": "admin"
    }
}
```

---

#### `POST /user/login`
Login WiFi menggunakan kode token. **Public**, tidak perlu Bearer token.

Request:
```json
{
    "code": "WCEVG"
}
```

Response `200`:
```json
{
    "success": true,
    "message": "Login berhasil. Selamat menikmati WiFi!",
    "data": {
        "code": "WCEVG",
        "valid_until": "2026-03-15T13:00:29.000000Z"
    }
}
```

Response `401`:
```json
{
    "success": false,
    "message": "Kode tidak valid, sudah digunakan, atau sudah kedaluwarsa."
}
```

---

### 🎫 Token Management

#### `GET /tokens`
Lihat semua token. Requires Bearer token.

Query params (opsional):
| Param | Value |
|-------|-------|
| status | `active` \| `used` \| `expired` |

Contoh: `GET /tokens?status=active`

Response `200`:
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 323,
                "code": "WCEVG",
                "is_used": false,
                "is_custom": false,
                "note": null,
                "valid_until": "2026-03-15T13:00:29.000000Z",
                "used_at": null,
                "created_at": "2026-03-15T07:00:29.000000Z",
                "updated_at": "2026-03-15T07:00:29.000000Z"
            }
        ],
        "per_page": 50,
        "total": 80
    }
}
```

---

#### `POST /tokens/generate`
Generate token harian secara manual. Requires Bearer token.

Response `200`:
```json
{
    "success": true,
    "message": "80 token berhasil di-generate.",
    "data": {
        "count": 80,
        "valid_until": "2026-03-15T13:02:56.000000Z"
    }
}
```

> Jumlah token mengikuti setting `token_count`. Durasi mengikuti `token_duration_hours`.

---

#### `POST /tokens/custom`
Tambah satu token custom (emergency). Requires Bearer token.

Request:
```json
{
    "note": "untuk tamu VIP"
}
```

Response `201`:
```json
{
    "success": true,
    "message": "Token custom berhasil dibuat.",
    "data": {
        "id": 401,
        "code": "X7KP2",
        "is_used": false,
        "is_custom": true,
        "note": "untuk tamu VIP",
        "valid_until": "2026-03-15T19:00:00.000000Z",
        "created_at": "2026-03-15T13:00:00.000000Z"
    }
}
```

---

#### `DELETE /tokens/{id}`
Hapus token yang belum dipakai. Requires Bearer token.

Response `200`:
```json
{
    "success": true,
    "message": "Token berhasil dihapus."
}
```

Response `422` (token sudah dipakai):
```json
{
    "success": false,
    "message": "Token yang sudah dipakai tidak bisa dihapus."
}
```

---

#### `GET /tokens/export/pdf`
Export semua token aktif ke PDF siap cetak. Requires Bearer token.

Response: File PDF (download otomatis). Layout 4 kolom per halaman, satu token per potongan kertas.

---

### ⚙️ Settings

#### `GET /settings`
Lihat semua konfigurasi. Requires Bearer token.

Response `200`:
```json
{
    "success": true,
    "data": {
        "token_count": "80",
        "token_duration_hours": "6",
        "wifi_download_speed": "10M",
        "wifi_upload_speed": "5M"
    }
}
```

---

#### `PUT /settings`
Update konfigurasi. Requires Bearer token.

Request:
```json
{
    "token_count": "100",
    "token_duration_hours": "8",
    "wifi_download_speed": "20M",
    "wifi_upload_speed": "10M"
}
```

Response `200`:
```json
{
    "success": true,
    "message": "Pengaturan berhasil disimpan."
}
```

---

### 📡 MikroTik

#### `GET /mikrotik/devices`
Lihat daftar perangkat yang sedang terhubung. Requires Bearer token.

Response `200`:
```json
{
    "success": true,
    "data": {
        "count": 3,
        "devices": [
            {
                "mac_address": "AA:BB:CC:DD:EE:FF",
                "ip_address": "192.168.1.101",
                "hostname": "android-device",
                "username": "AB1C2",
                "uptime": "00:30:45"
            }
        ]
    }
}
```

---

#### `PUT /mikrotik/speed`
Ubah kecepatan WiFi. Requires Bearer token.

Request:
```json
{
    "download": "20M",
    "upload": "10M"
}
```

Response `200`:
```json
{
    "success": true,
    "message": "Kecepatan WiFi berhasil diubah.",
    "data": {
        "download": "20M",
        "upload": "10M"
    }
}
```

Response `500` (MikroTik tidak terhubung):
```json
{
    "success": false,
    "message": "Gagal mengubah kecepatan. Cek koneksi ke MikroTik."
}
```

---

## Ringkasan Endpoint

| Method | Endpoint | Auth | Keterangan |
|--------|----------|------|------------|
| POST | `/admin/login` | Public | Login admin |
| POST | `/admin/logout` | Bearer | Logout admin |
| GET | `/admin/profile` | Bearer | Lihat profil |
| PUT | `/admin/profile` | Bearer | Update profil |
| POST | `/user/login` | Public | Login WiFi via kode |
| GET | `/tokens` | Bearer | List semua token |
| POST | `/tokens/generate` | Bearer | Generate token manual |
| POST | `/tokens/custom` | Bearer | Tambah token custom |
| DELETE | `/tokens/{id}` | Bearer | Hapus token |
| GET | `/tokens/export/pdf` | Bearer | Export PDF |
| GET | `/settings` | Bearer | Lihat settings |
| PUT | `/settings` | Bearer | Update settings |
| GET | `/mikrotik/devices` | Bearer | List perangkat aktif |
| PUT | `/mikrotik/speed` | Bearer | Set kecepatan WiFi |

---

## Catatan

- Token bersifat **sekali pakai** — setelah digunakan tidak bisa dipakai lagi.
- Token di-generate otomatis setiap hari jam **07:00 WIB** via scheduler.
- Format kode token: **5 karakter**, kombinasi huruf kapital + angka (contoh: `AB1C2`).
- Hanya ada **satu akun admin**, tidak bisa ditambah, hanya bisa diedit.
- Koneksi ke MikroTik menggunakan **RouterOS API** port `8728`.
