<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'token_count',          'value' => '80',    'description' => 'Jumlah token yang di-generate setiap hari'],
            ['key' => 'token_duration_hours',  'value' => '6',     'description' => 'Durasi masa aktif token dalam jam'],
            ['key' => 'wifi_download_speed',   'value' => '10M',   'description' => 'Batas kecepatan download'],
            ['key' => 'wifi_upload_speed',     'value' => '5M',    'description' => 'Batas kecepatan upload'],
        ];

        foreach ($defaults as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}