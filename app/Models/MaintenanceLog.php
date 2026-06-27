<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends Model
{
    protected $fillable = [
        'ambulance_id', 'maintenance_type', 'description', 'performed_by', 'cost',
        'odometer_km', 'scheduled_date', 'performed_date', 'next_due_date', 'next_due_km',
        'status', 'parts_replaced', 'remarks', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'scheduled_date' => 'date',
            'performed_date' => 'date',
            'next_due_date' => 'date',
        ];
    }

    public function ambulance(): BelongsTo
    {
        return $this->belongsTo(Ambulance::class);
    }
}
