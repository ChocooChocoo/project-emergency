<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelLog extends Model
{
    protected $fillable = [
        'ambulance_id', 'log_date', 'liters', 'cost_per_liter', 'total_cost',
        'odometer_km', 'fuel_type', 'station', 'filled_by', 'remarks', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'liters' => 'decimal:2',
            'cost_per_liter' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }

    public function ambulance(): BelongsTo
    {
        return $this->belongsTo(Ambulance::class);
    }
}
