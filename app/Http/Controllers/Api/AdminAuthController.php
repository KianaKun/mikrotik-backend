<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('username', $request->username)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            throw ValidationException::withMessages([
                'username' => ['Username atau password salah.'],
            ]);
        }

        $admin->tokens()->delete();
        $token = $admin->createToken('admin-token', ['role:admin'])->plainTextToken;

        return response()->json([
            'success' => true,
            'data'    => [
                'token' => $token,
                'admin' => ['id' => $admin->id, 'name' => $admin->name, 'username' => $admin->username],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Logout berhasil.']);
    }

    public function profile(Request $request): JsonResponse
    {
        $admin = $request->user();
        return response()->json([
            'success' => true,
            'data'    => ['id' => $admin->id, 'name' => $admin->name, 'username' => $admin->username],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name'              => 'sometimes|string|max:100',
            'username'          => 'sometimes|string|max:50|unique:admins,username,' . $request->user()->id,
            'password'          => 'sometimes|string|min:8|confirmed',
        ]);

        $data = $request->only(['name', 'username']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $request->user()->update($data);
        $admin = $request->user()->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data'    => ['id' => $admin->id, 'name' => $admin->name, 'username' => $admin->username],
        ]);
    }
}