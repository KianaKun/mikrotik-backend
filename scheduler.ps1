while ($true) {
    Set-Location "D:\New folder\mikrotik-backend"
    php artisan schedule:run
    Start-Sleep -Seconds 60
}