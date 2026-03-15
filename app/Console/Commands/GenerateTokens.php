<?php

namespace App\Console\Commands;

use App\Services\MikroTikService;
use App\Services\TokenService;
use Illuminate\Console\Command;

class GenerateTokens extends Command
{
    protected $signature   = 'tokens:generate';
    protected $description = 'Generate token harian dan sync ke MikroTik';

    public function handle(TokenService $tokenService, MikroTikService $mikrotikService): void
    {
        $this->info('Memulai generate token...');

        $tokens = $tokenService->generateDailyTokens();
        $this->info(count($tokens) . ' token berhasil di-generate.');

        if (config('mikrotik.host') && config('mikrotik.host') !== '192.168.1.1') {
            $this->info('Sync ke MikroTik...');
            try {
                $mikrotikService->syncTokens($tokens);
                $this->info('Sync ke MikroTik berhasil.');
            } catch (\Exception $e) {
                $this->warn('Sync ke MikroTik gagal: ' . $e->getMessage());
                $this->warn('Token tetap tersimpan di database.');
            }
        } else {
            $this->warn('MikroTik host belum dikonfigurasi, skip sync.');
        }

        $this->info('Selesai! Valid hingga: ' . $tokens[0]->valid_until);
    }
}