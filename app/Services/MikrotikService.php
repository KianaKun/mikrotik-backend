<?php

namespace App\Services;

use App\Models\Setting;
use RouterOS\Client;
use RouterOS\Query;
use Exception;
use Illuminate\Support\Facades\Log;

class MikroTikService
{
    protected ?Client $client = null;

    protected function connect(): Client
    {
        if ($this->client) return $this->client;

        $this->client = new Client([
            'host'     => config('mikrotik.host'),
            'user'     => config('mikrotik.username'),
            'pass'     => config('mikrotik.password'),
            'port'     => (int) config('mikrotik.port'),
            'attempts' => 5,
            'delay'    => 1,
        ]);

        return $this->client;
    }

    public function addHotspotUser(string $code, string $validUntil): bool
    {
        try {
            $query = (new Query('/ip/hotspot/user/add'))
                ->equal('name', $code)
                ->equal('password', $code)
                ->equal('server', config('mikrotik.hotspot_server'))
                ->equal('comment', 'auto|' . $validUntil);

            $this->connect()->query($query)->read();
            return true;
        } catch (Exception $e) {
            Log::error('MikroTik addHotspotUser: ' . $e->getMessage());
            return false;
        }
    }

    public function removeHotspotUser(string $code): bool
    {
        try {
            $response = $this->connect()
                ->query((new Query('/ip/hotspot/user/print'))->where('name', $code))
                ->read();

            if (empty($response)) return true;

            $this->connect()
                ->query((new Query('/ip/hotspot/user/remove'))->equal('.id', $response[0]['.id']))
                ->read();

            return true;
        } catch (Exception $e) {
            Log::error('MikroTik removeHotspotUser: ' . $e->getMessage());
            return false;
        }
    }

    public function getActiveDevices(): array
    {
        try {
            $response = $this->connect()
                ->query(new Query('/ip/hotspot/active/print'))
                ->read();

            return array_map(fn($item) => [
                'mac_address' => $item['mac-address'] ?? '',
                'ip_address'  => $item['address'] ?? '',
                'hostname'    => $item['host-name'] ?? 'Unknown',
                'username'    => $item['user'] ?? '',
                'uptime'      => $item['uptime'] ?? '',
            ], $response);
        } catch (Exception $e) {
            Log::error('MikroTik getActiveDevices: ' . $e->getMessage());
            return [];
        }
    }

    public function setWifiSpeed(string $download, string $upload): bool
    {
        try {
            $response = $this->connect()
                ->query((new Query('/ip/hotspot/user/profile/print'))->where('name', 'default'))
                ->read();

            if (empty($response)) return false;

            $this->connect()
                ->query((new Query('/ip/hotspot/user/profile/set'))
                    ->equal('.id', $response[0]['.id'])
                    ->equal('rate-limit', "{$upload}/{$download}"))
                ->read();

            Setting::set('wifi_download_speed', $download);
            Setting::set('wifi_upload_speed', $upload);

            return true;
        } catch (Exception $e) {
            Log::error('MikroTik setWifiSpeed: ' . $e->getMessage());
            return false;
        }
    }

    public function syncTokens(array $tokens): void
    {
        foreach ($tokens as $token) {
            $this->addHotspotUser($token->code, $token->valid_until->toDateTimeString());
        }
    }
}