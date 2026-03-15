<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAuthController extends Controller
{
    public function __construct(protected TokenService $tokenService) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|size:5']);

        $token = $this->tokenService->useToken($request->code);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Kode tidak valid, sudah digunakan, atau sudah kedaluwarsa.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil. Selamat menikmati WiFi!',
            'data'    => ['code' => $token->code, 'valid_until' => $token->valid_until],
        ]);
    }
}