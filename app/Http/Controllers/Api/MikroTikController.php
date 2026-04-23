<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\MikroTikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class MikroTikController extends Controller
{
    public function __construct(protected MikroTikService $mikrotikService) {}

    public function devices(Request $request): JsonResponse
    {
        $devices = $this->mikrotikService->getActiveDevices();

        $page = $request->get('page', 1);
        $perPage = 50;

        $collection = collect($devices);

        $paginated = new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page
        );

        return response()->json([
            'success' => true,
            'data' => [
                'current_page' => $paginated->currentPage(),
                'data'         => $paginated->items(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ]
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
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah kecepatan. Cek koneksi ke MikroTik.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kecepatan WiFi berhasil diubah.',
            'data'    => ['download' => $request->download, 'upload' => $request->upload],
        ]);
    }
    public function disconnect(Request $request): JsonResponse
    {
        $request->validate([
            'ip' => 'required|ip'
        ]);

        $ok = $this->mikrotikService->disconnectDevice($request->ip);

        if (!$ok) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal disconnect device'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Device berhasil di-disconnect'
        ]);
    }
}