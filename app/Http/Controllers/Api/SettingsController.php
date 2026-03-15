<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key');
        return response()->json(['success' => true, 'data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'token_count'         => 'sometimes|integer|min:1|max:500',
            'token_duration_hours'=> 'sometimes|integer|min:1|max:24',
            'wifi_download_speed' => 'sometimes|string|max:20',
            'wifi_upload_speed'   => 'sometimes|string|max:20',
        ]);

        foreach ($request->only(['token_count', 'token_duration_hours', 'wifi_download_speed', 'wifi_upload_speed']) as $key => $value) {
            Setting::set($key, $value);
        }

        return response()->json(['success' => true, 'message' => 'Pengaturan berhasil disimpan.']);
    }
}