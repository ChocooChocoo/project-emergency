<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchAssignment extends Model
{
    protected $fillable = [
        'incident_id', 'organization_id', 'dispatcher_user_id', 'assigned_by',
        'ambulance_id', 'driver_user_id', 'hospital_id', 'status', 'care_status',
        'dss_rank', 'assigned_at', 'response_deadline_at', 'accepted_at', 'en_route_at',
        'arrived_on_scene_at', 'transport_started_at', 'arrived_at_hospital_at',
        'handover_completed_at', 'completed_at', 'ended_at', 'dispatch_notes', 'notes',
    ];

    /** Driver status machine, in order. advance() steps along this list. */
    public const FLOW = [
        'assigned', 'accepted', 'en_route', 'arrived_on_scene',
        'transporting', 'arrived_at_hospital', 'completed',
    ];

    /** assignment status → matching timestamp column → incident status mirror. */
    public const MILESTONES = [
        'accepted' => ['accepted_at', 'dispatched'],
        'en_route' => ['en_route_at', 'ongoing'],
        'arrived_on_scene' => ['arrived_on_scene_at', 'on_scene'],
        'transporting' => ['transport_started_at', 'transporting'],
        'arrived_at_hospital' => ['arrived_at_hospital_at', 'arrived_at_hospital'],
        'completed' => ['completed_at', 'completed'],
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'response_deadline_at' => 'datetime',
            'accepted_at' => 'datetime',
            'en_route_at' => 'datetime',
            'arrived_on_scene_at' => 'datetime',
            'transport_started_at' => 'datetime',
            'arrived_at_hospital_at' => 'datetime',
            'handover_completed_at' => 'datetime',
            'completed_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function ambulance(): BelongsTo
    {
        return $this->belongsTo(Ambulance::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatcher_user_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }
}
