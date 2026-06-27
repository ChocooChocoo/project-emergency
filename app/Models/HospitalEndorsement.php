<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalEndorsement extends Model
{
    protected $fillable = [
        'dispatch_assignment_id', 'incident_id', 'hospital_id', 'endorsed_by', 'received_by',
        'status', 'responded_by', 'responded_at', 'response_notes', 'received_at', 'arrived_at',
        'handoff_confirmed_at', 'outcome_note', 'handoff_status', 'notes', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
            'received_at' => 'datetime',
            'arrived_at' => 'datetime',
            'handoff_confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }
}
