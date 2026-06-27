<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'code', 'name', 'price', 'billing_cycle',
        'max_dispatchers', 'max_drivers', 'max_ambulances', 'max_hospitals',
        'max_members', 'max_roles_assignable',
        'is_unlimited', 'is_public', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_unlimited' => 'boolean',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
