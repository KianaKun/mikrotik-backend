<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\MikroTikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MikroTikController extends Controller
{
    public function __construct(protected MikroTikService $mikrotikService) {}

    public function devices(): JsonResponse
    {
        $devices = $this->mikrotikService->getActiveDevices();

        return response()->json([
            'success' => true,
            'data'    => ['count' => count($devices), 'devices' => $devices],
        ]);
    }

    public function setSpeed(Request $request): JsonResponse
    {
        $request->validate([
            'download' => 'required|string|max:20',
            'upload'   => 'required|string|max:20',
        ]);

        $ok = $this->mikrotikService->setWifiSpeed($request->download, $request->upload);

        if (!$ok) {
            return response()->json(['success' => false, 'message' => 'Gagal mengubah kecepatan. Cek koneksi ke MikroTik.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kecepatan WiFi berhasil diubah.',
            'data'    => ['download' => $request->download, 'upload' => $request->upload],
        ]);
    }
}