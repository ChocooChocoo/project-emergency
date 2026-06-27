<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationCoverageArea extends Model
{
    protected $fillable = [
        'organization_id', 'coverage_name', 'area_name', 'barangay_name',
        'polygon_json', 'coordinates_json', 'priority_rank', 'is_overlap_allowed', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'polygon_json' => 'array',
            'coordinates_json' => 'array',
            'is_overlap_allowed' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
