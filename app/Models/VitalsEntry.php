<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VitalsEntry extends Model
{
    public $timestamps = false; // table carries recorded_at only.

    protected $fillable = [
        'incident_id', 'recorded_at', 'bp_systolic', 'bp_diastolic', 'pulse_rate',
        'respiratory_rate', 'temperature_c', 'oxygen_saturation', 'blood_glucose',
        'gcs_score', 'pain_score', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'temperature_c' => 'decimal:1',
            'blood_glucose' => 'decimal:2',
        ];
    }
}
