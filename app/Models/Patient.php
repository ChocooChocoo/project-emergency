<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'incident_id', 'full_name', 'sex', 'birth_date', 'phone', 'address', 'created_by',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }
}
