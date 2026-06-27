<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'account_type', 'organization_id', 'hospital_id', 'requested_role',
        'first_name', 'middle_name', 'last_name', 'suffix',
        'email', 'phone', 'alt_phone', 'password',
        'account_status', 'terms_accepted_at', 'terms_version',
        'email_verified_at', 'is_approved', 'is_active',
        'is_archived', 'archived_at', 'archived_by', 'archive_reason',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'approved_at' => 'datetime',
            'last_login_at' => 'datetime',
            'archived_at' => 'datetime',
            'is_approved' => 'boolean',
            'is_active' => 'boolean',
            'is_archived' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /** R13: full_name computed, not stored. */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim(implode(' ', array_filter(
            [$this->first_name, $this->middle_name, $this->last_name, $this->suffix]
        ))));
    }

    // --- RBAC (S2) ---

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('organization_id', 'assigned_by', 'assigned_at');
    }

    /** Direct per-user permission grants (user_permissions). */
    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('organization_id', 'granted_by');
    }

    public function isSuperAdmin(): bool
    {
        return $this->account_type === 'super_admin';
    }

    /** All effective permission codes: roles' perms ∪ direct grants. */
    public function permissionCodes(): Collection
    {
        return $this->roles->flatMap(fn (Role $r) => $r->permissions->pluck('code'))
            ->merge($this->directPermissions->pluck('code'))
            ->unique()
            ->values();
    }

    /**
     * Access derives entirely from seeded role/direct permissions — no super_admin wildcard.
     * isSuperAdmin() is now a label only. Break-glass = a seeder/DB change.
     * ponytail: no separate developer_root bypass tier until it's actually needed.
     */
    public function hasPermission(string $code): bool
    {
        return $this->permissionCodes()->contains($code);
    }
}
