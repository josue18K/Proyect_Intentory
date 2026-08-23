<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public function movements() { return $this->hasMany(InventoryMovement::class); }
    public function auditLogs() { return $this->hasMany(AuditLog::class); }
    public function createdLicenses() { return $this->hasMany(License::class, 'created_by'); }
    public function transfers() { return $this->hasMany(Transfer::class); }
    public function branches() { return $this->belongsToMany(Branch::class); }
    public function canAccessBranch(int $branchId): bool { return $this->role === 'administrador' || $this->branches()->whereKey($branchId)->exists(); }
    public function can($abilities, $arguments = []): bool { foreach ((array) $abilities as $ability) if ($this->role === 'administrador' || in_array($ability, $this->permissions ?? [], true)) return true; return false; }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'is_active',
        'password',
        'permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
    }
}
