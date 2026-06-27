<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $fillable = [
        'device_uuid', 'user_id', 'false_alarm_count', 'last_flagged_at', 'is_blocked', 'blocked_at',
    ];

    protected function casts(): array
    {
        return [
            'false_alarm_count' => 'integer',
            'last_flagged_at' => 'datetime',
            'is_blocked' => 'boolean',
            'blocked_at' => 'datetime',
        ];
    }
}
