<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Incident extends Model
{
    protected $fillable = [
        'request_code', 'request_type', 'scheduled_for', 'master_incident_id',
        'user_id', 'guest_id', 'organization_id', 'coverage_area_id',
        'patient_name', 'contact_number', 'incident_type', 'priority_label',
        'severity', 'patient_count', 'pickup_lat', 'pickup_lng', 'pickup_address', 'pickup_landmark',
        'destination_hospital_id', 'status', 'request_summary', 'notes', 'is_public_tracking',
    ];

    /** Statuses still considered "open" for heatmap grouping. */
    public const OPEN_STATUSES = ['pending', 'dispatched', 'ongoing', 'on_scene', 'transporting'];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'severity' => 'integer',
            'patient_count' => 'integer',
            'pickup_lat' => 'decimal:8',
            'pickup_lng' => 'decimal:8',
            'is_public_tracking' => 'boolean',
            'is_flagged_for_abuse' => 'boolean',
            'is_archived' => 'boolean',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(GuestSession::class, 'guest_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function masterIncident(): BelongsTo
    {
        return $this->belongsTo(self::class, 'master_incident_id');
    }

    public function childReports(): HasMany
    {
        return $this->hasMany(self::class, 'master_incident_id');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class)->orderBy('created_at');
    }

    public function dispatchAssignments(): HasMany
    {
        return $this->hasMany(DispatchAssignment::class);
    }

    /** The current live assignment, if any (latest non-terminal). */
    public function activeAssignment(): HasOne
    {
        return $this->hasOne(DispatchAssignment::class)
            ->whereNotIn('status', ['completed', 'cancelled', 'reassigned', 'timed_out'])
            ->latestOfMany();
    }

    public function destinationHospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'destination_hospital_id');
    }

    public function vitals(): HasMany
    {
        return $this->hasMany(VitalsEntry::class)->orderByDesc('recorded_at');
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(TreatmentRecord::class)->orderByDesc('performed_at');
    }

    public function prehospitalNotes(): HasMany
    {
        return $this->hasMany(PrehospitalNote::class)->orderByDesc('created_at');
    }

    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    public function endorsements(): HasMany
    {
        return $this->hasMany(HospitalEndorsement::class)->orderByDesc('id');
    }
}
