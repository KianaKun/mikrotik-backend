<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    protected $fillable = [
        'code', 'is_used', 'is_custom',
        'note', 'valid_until', 'used_at',
    ];

    protected function casts(): array
    {
        return [
            'is_used'     => 'boolean',
            'is_custom'   => 'boolean',
            'valid_until' => 'datetime',
            'used_at'     => 'datetime',
        ];
    }

    // ── Scopes ────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_used', false)
                     ->where('valid_until', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<=', now());
    }

    public function scopeUsed($query)
    {
        return $query->where('is_used', true);
    }

    // ── Helpers ───────────────────────────────────────────
    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    public function isValid(): bool
    {
        return !$this->is_used && !$this->isExpired();
    }
}