<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ambulance extends Model
{
    protected $fillable = [
        'organization_id', 'plate_no', 'unit_code', 'vehicle_name', 'vehicle_type',
        'tier', 'doh_credential_ref',
        'has_ventilator', 'has_oxygen', 'has_aed', 'has_spine_board', 'has_ob_kit', 'has_stretcher',
        'capacity_patients', 'equipment_notes', 'current_driver_user_id',
        'readiness_status', 'status', 'is_serviceable', 'current_odometer_km',
        'next_maintenance_date', 'notes', 'last_lat', 'last_lng', 'last_seen_at',
        'is_archived', 'archived_at', 'archived_by', 'archive_reason',
    ];

    /** Equipment flag columns, in display order. */
    public const EQUIPMENT = [
        'has_ventilator' => 'Ventilator',
        'has_oxygen' => 'Oxygen',
        'has_aed' => 'AED',
        'has_spine_board' => 'Spine board',
        'has_ob_kit' => 'OB kit',
        'has_stretcher' => 'Stretcher',
    ];

    protected function casts(): array
    {
        return [
            'has_ventilator' => 'boolean',
            'has_oxygen' => 'boolean',
            'has_aed' => 'boolean',
            'has_spine_board' => 'boolean',
            'has_ob_kit' => 'boolean',
            'has_stretcher' => 'boolean',
            'is_serviceable' => 'boolean',
            'is_archived' => 'boolean',
            'current_odometer_km' => 'decimal:2',
            'next_maintenance_date' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function currentDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_driver_user_id');
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class);
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DispatchAssignment::class);
    }
}
