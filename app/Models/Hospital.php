<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hospital extends Model
{
    protected $fillable = [
        'organization_id', 'name', 'facility_type', 'ownership', 'ambulance_status',
        'city', 'province', 'address', 'phone', 'lat', 'lng',
        'capacity_status', 'available_beds', 'is_er_open', 'notes', 'created_by',
        'is_active', 'is_archived', 'archived_at', 'archived_by', 'archive_reason',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:8',
            'lng' => 'decimal:8',
            'available_beds' => 'integer',
            'is_er_open' => 'boolean',
            'is_active' => 'boolean',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function endorsements(): HasMany
    {
        return $this->hasMany(HospitalEndorsement::class);
    }
}
