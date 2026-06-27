<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    protected $fillable = [
        'org_type', 'name', 'org_acronym', 'registration_permit_number', 'code',
        'email', 'phone', 'dispatch_hotline_ops', 'base_station_location',
        'service_type', 'is_24_7', 'admin_contact_title', 'address', 'service_city',
        'covered_barangays_json', 'coverage_summary', 'hq_latitude', 'hq_longitude',
        'service_radius_km', 'organization_status', 'admin_user_id',
        'is_approved', 'is_active', 'approved_by', 'approved_at', 'rejected_reason',
        'onboarding_reviewer_notes', 'org_profile_completed_at',
        'is_archived', 'archived_at', 'archived_by', 'archive_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_24_7' => 'boolean',
            'is_approved' => 'boolean',
            'is_active' => 'boolean',
            'is_archived' => 'boolean',
            'approved_at' => 'datetime',
            'archived_at' => 'datetime',
            'org_profile_completed_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(OrganizationSubscription::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OrganizationDocument::class);
    }

    public function coverageAreas(): HasMany
    {
        return $this->hasMany(OrganizationCoverageArea::class);
    }

    public function ambulances(): HasMany
    {
        return $this->hasMany(Ambulance::class);
    }
}
