<?php

return [
    'host'           => env('MIKROTIK_HOST', '192.168.1.1'),
    'port'           => env('MIKROTIK_PORT', 8728),
    'username'       => env('MIKROTIK_USERNAME', 'admin'),
    'password'       => env('MIKROTIK_PASSWORD', ''),
    'hotspot_server' => env('MIKROTIK_HOTSPOT_SERVER', 'hotspot1'),
];