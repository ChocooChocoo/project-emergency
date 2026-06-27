<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentRecord extends Model
{
    public $timestamps = false; // table carries performed_at only.

    protected $fillable = [
        'incident_id', 'performed_at', 'treatment_type', 'details', 'created_by',
    ];

    protected function casts(): array
    {
        return ['performed_at' => 'datetime'];
    }
}
