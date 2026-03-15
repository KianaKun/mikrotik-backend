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

        match ($request->query('status')) {
            'active'  => $query->active(),
            'used'    => $query->used(),
            'expired' => $query->expired(),
            default   => null,
        };

        return response()->json(['success' => true, 'data' => $query->paginate(50)]);
    }

    public function generate(): JsonResponse
    {
        $tokens = $this->tokenService->generateDailyTokens();
        $this->mikrotikService->syncTokens($tokens);

        return response()->json([
            'success' => true,
            'message' => count($tokens) . ' token berhasil di-generate.',
            'data'    => ['count' => count($tokens), 'valid_until' => $tokens[0]->valid_until ?? null],
        ]);
    }

    public function addCustom(Request $request): JsonResponse
    {
        $request->validate(['note' => 'nullable|string|max:200']);

        $token = $this->tokenService->generateCustomToken($request->note ?? '');
        $this->mikrotikService->addHotspotUser($token->code, $token->valid_until->toDateTimeString());

        return response()->json(['success' => true, 'message' => 'Token custom berhasil dibuat.', 'data' => $token], 201);
    }

    public function destroy(Token $token): JsonResponse
    {
        if ($token->is_used) {
            return response()->json(['success' => false, 'message' => 'Token yang sudah dipakai tidak bisa dihapus.'], 422);
        }

        $this->mikrotikService->removeHotspotUser($token->code);
        $token->delete();

        return response()->json(['success' => true, 'message' => 'Token berhasil dihapus.']);
    }

    public function exportPdf()
    {
        $tokens = Token::active()->orderBy('code')->get();
        $pdf    = Pdf::loadView('pdf.tokens', ['tokens' => $tokens]);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('tokens-' . now()->format('Ymd-His') . '.pdf');
    }
}