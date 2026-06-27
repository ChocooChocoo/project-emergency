<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentUpdate extends Model
{
    public $timestamps = false; // table has only created_at (useCurrent).

    protected $fillable = [
        'incident_id', 'dispatch_assignment_id', 'status', 'care_status',
        'update_type', 'note', 'visibility', 'created_by', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
