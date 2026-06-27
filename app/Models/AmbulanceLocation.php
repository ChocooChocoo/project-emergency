<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmbulanceLocation extends Model
{
    public $timestamps = false; // table carries recorded_at only.

    protected $fillable = ['ambulance_id', 'dispatch_assignment_id', 'lat', 'lng', 'recorded_at'];

    protected function casts(): array
    {
        return ['lat' => 'decimal:7', 'lng' => 'decimal:7', 'recorded_at' => 'datetime'];
    }
}
