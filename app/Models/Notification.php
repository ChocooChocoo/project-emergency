<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** In-app notification (tbl_notifications). Created-only; the table has no updated_at. */
class Notification extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'type', 'title', 'message', 'data_json', 'is_read', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data_json' => 'array',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
