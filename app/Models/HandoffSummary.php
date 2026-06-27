<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HandoffSummary extends Model
{
    protected $fillable = [
        'incident_id', 'summary', 'outcome', 'handoff_to', 'handoff_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['handoff_at' => 'datetime'];
    }
}
