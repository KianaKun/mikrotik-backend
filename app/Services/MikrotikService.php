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

    public function addToAddressList(string $ip, $validUntil): bool
    {
        try {
            $timeout = now()->diffInSeconds($validUntil);
            $timeoutFormatted = gmdate('H:i:s', $timeout);

            $query = (new Query('/ip/firewall/address-list/add'))
                ->equal('list', 'sudah-login')
                ->equal('address', $ip)
                ->equal('timeout', $timeoutFormatted)
                ->equal('comment', 'token:' . $ip);

            $this->connect()->query($query)->read();
            return true;
        } catch (Exception $e) {
            Log::error('MikroTik addToAddressList: ' . $e->getMessage());
            return false;
        }
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
                ->query((new Query('/ip/dhcp-server/lease/print'))
                    ->where('status', 'bound'))
                ->read();

            return array_map(fn($item) => [
                'mac_address' => $item['mac-address'] ?? '',
                'ip_address'  => $item['address'] ?? '',
                'hostname'    => $item['host-name'] ?? 'Unknown',
                'username'    => '',
                'uptime'      => '',
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

    public function disconnectDevice(string $ip): bool
    {
        try {
            $client = $this->connect();

            // 1. REMOVE address-list
            $list = $client
                ->query((new \RouterOS\Query('/ip/firewall/address-list/print'))
                    ->where('address', $ip)
                    ->where('list', 'sudah-login'))
                ->read();

            foreach ($list as $item) {
                $client
                    ->query((new \RouterOS\Query('/ip/firewall/address-list/remove'))
                        ->equal('.id', $item['.id']))
                    ->read();
            }

            // 2. REMOVE ARP 
            $arp = $client
                ->query((new \RouterOS\Query('/ip/arp/print'))
                    ->where('address', $ip))
                ->read();

            foreach ($arp as $item) {
                $client
                    ->query((new \RouterOS\Query('/ip/arp/remove'))
                        ->equal('.id', $item['.id']))
                    ->read();
            }

            // 3. REMOVE DHCP LEASE
            $leases = $client
                ->query((new \RouterOS\Query('/ip/dhcp-server/lease/print'))
                    ->where('address', $ip))
                ->read();

            foreach ($leases as $lease) {
                // kalau mau lebih keras, bisa disable dulu
                $client
                    ->query((new \RouterOS\Query('/ip/dhcp-server/lease/remove'))
                        ->equal('.id', $lease['.id']))
                    ->read();
            }

            return true;

        } catch (\Exception $e) {
            \Log::error('Disconnect Device: ' . $e->getMessage());
            return false;
        }
    }
}