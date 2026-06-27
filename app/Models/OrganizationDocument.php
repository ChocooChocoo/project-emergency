<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationDocument extends Model
{
    protected $fillable = [
        'organization_id', 'document_type', 'document_number', 'file_path',
        'validation_status', 'is_optional', 'validated_by', 'validated_at', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_optional' => 'boolean',
            'validated_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
