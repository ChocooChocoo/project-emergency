<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestSession extends Model
{
    protected $fillable = [
        'guest_key', 'phone', 'ip_first_seen', 'user_agent',
        'requests_limit', 'requests_used', 'upgraded_user_id',
        'is_active', 'last_seen_at', 'disabled_at',
    ];

    protected function casts(): array
    {
        return [
            'requests_limit' => 'integer',
            'requests_used' => 'integer',
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    public function hasQuotaRemaining(): bool
    {
        return $this->is_active && $this->requests_used < $this->requests_limit;
    }
}
