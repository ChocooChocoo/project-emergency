<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrehospitalNote extends Model
{
    public $timestamps = false; // table carries created_at only.

    protected $fillable = ['incident_id', 'note_type', 'content', 'created_by', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
