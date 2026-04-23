<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Token;
use App\Services\MikroTikService;
use App\Services\TokenService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function __construct(
        protected TokenService    $tokenService,
        protected MikroTikService $mikrotikService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Token::latest();

        $status = $request->query('status');

        match ($status) {
            'active'  => $query->active(),
            'used'    => $query->used(),
            'expired' => $query->expired(),
            default   => null,
        };

        $tokens = $query->paginate(50);

        $isGenerate = Token::whereDate('created_at', today())->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'current_page' => $tokens->currentPage(),
                'data'         => $tokens->items(),
                'per_page'     => $tokens->perPage(),
                'total'        => $tokens->total(),
                'status'       => $status,
                'is_generate'  => $isGenerate,
            ]
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $token = Token::where('code', $request->token)
            ->where('is_used', false)
            ->first();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau sudah digunakan'
            ], 401);
        }

        // tandai token dipakai
        $token->update([
            'is_used' => true,
            'used_at' => now()
        ]);

        // ambil IP client
        $ip = $request->ip();

        // inject ke MikroTik
        $ok = $this->mikrotikService->addToAddressList($ip, $token->valid_until);

        if (!$ok) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal authorize ke MikroTik'
            ], 500);
        }

        // ❗ penting: jangan redirect
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'ip' => $ip,
            'valid_until' => $token->valid_until
        ]);
    }

    public function generate(): JsonResponse
    {
        $tokens = $this->tokenService->generateDailyTokens();
        // $this->mikrotikService->syncTokens($tokens);

        return response()->json([
            'success' => true,
            'message' => count($tokens) . ' token berhasil di-generate.',
            'data'    => [
                'count'       => count($tokens),
                'valid_until' => $tokens[0]->valid_until ?? null,
            ],
        ]);
    }

    public function addCustom(): JsonResponse
    {
        $token = $this->tokenService->generateCustomToken('');
        // $this->mikrotikService->addHotspotUser($token->code, $token->valid_until->toDateTimeString());

        return response()->json([
            'success' => true,
            'message' => 'Token custom berhasil dibuat.',
            'data'    => $token,
        ], 201);
    }

    public function destroy(Token $token): JsonResponse
    {
        if ($token->is_used) {
            return response()->json([
                'success' => false,
                'message' => 'Token yang sudah dipakai tidak bisa dihapus.',
            ], 422);
        }

        // $this->mikrotikService->removeHotspotUser($token->code);
        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'Token berhasil dihapus.',
        ]);
    }

    public function exportPdf()
    {
        $tokens = Token::active()->orderBy('code')->get();
        $pdf    = Pdf::loadView('pdf.tokens', ['tokens' => $tokens]);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('tokens-' . now()->format('Ymd-His') . '.pdf');
    }
}