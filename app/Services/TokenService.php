<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Token;
use Illuminate\Support\Str;

class TokenService
{
    public function generateDailyTokens(): array
    {
        $count      = (int) Setting::get('token_count', 80);
        $duration   = (int) Setting::get('token_duration_hours', 6);
        $validUntil = now()->addHours($duration);
        $generated  = [];

        for ($i = 0; $i < $count; $i++) {
            $generated[] = Token::create([
                'code'        => $this->generateUniqueCode(),
                'valid_until' => $validUntil,
                'is_custom'   => false,
            ]);
        }

        return $generated;
    }

    public function generateCustomToken(string $note = ''): Token
    {
        $duration = (int) Setting::get('token_duration_hours', 6);

        return Token::create([
            'code'        => $this->generateUniqueCode(),
            'valid_until' => now()->addHours($duration),
            'is_custom'   => true,
            'note'        => $note,
        ]);
    }

    public function useToken(string $code): ?Token
    {
        $token = Token::where('code', strtoupper($code))
                      ->where('is_used', false)
                      ->where('valid_until', '>', now())
                      ->first();

        if (!$token) return null;

        $token->update(['is_used' => true, 'used_at' => now()]);

        return $token;
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(5));
        } while (Token::where('code', $code)->exists());

        return $code;
    }
}