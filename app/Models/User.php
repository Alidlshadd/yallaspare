<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';
    public const ROLE_DEALER = 'dealer';
    public const DEALER_STATUS_ACTIVE = 'active';
    public const DEALER_STATUS_INACTIVE = 'inactive';
    public const DEALER_STATUS_SUSPENDED = 'suspended';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'dealer_status',
        'dealer_discount',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'dealer_discount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (empty($user->attributes['role'])) {
                $user->attributes['role'] = self::ROLE_USER;
            }
        });
    }

    public static function allowedRoles(): array
    {
        return [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_DEALER, self::ROLE_USER];
    }

    public static function allowedDealerStatuses(): array
    {
        return [
            self::DEALER_STATUS_ACTIVE,
            self::DEALER_STATUS_INACTIVE,
            self::DEALER_STATUS_SUSPENDED,
        ];
    }

    public static function normalizeRole(?string $role): string
    {
        $normalized = strtolower(trim((string) $role));

        return match ($normalized) {
            'super-admin', 'superadmin' => self::ROLE_SUPER_ADMIN,
            'administrator' => self::ROLE_ADMIN,
            'customer', '' => self::ROLE_USER,
            'manager' => self::ROLE_DEALER,
            default => in_array($normalized, self::allowedRoles(), true)
                ? $normalized
                : self::ROLE_USER,
        };
    }

    public function setRoleAttribute(?string $value): void
    {
        $role = self::normalizeRole($value);
        $this->attributes['role'] = $role;
    }

    public function getRoleAttribute(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return self::ROLE_USER;
        }

        return self::normalizeRole($value);
    }

    public function setIsAdminAttribute(bool|int|string|null $value): void
    {
        $isAdmin = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $isAdmin = $isAdmin ?? ((int) $value === 1);

        if ($isAdmin) {
            $currentRole = $this->attributes['role'] ?? null;
            if ($currentRole === null || !in_array(self::normalizeRole($currentRole), [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN], true)) {
                $this->attributes['role'] = self::ROLE_ADMIN;
            }
            unset($this->attributes['is_admin']);
            return;
        }

        $currentRole = $this->attributes['role'] ?? null;
        if ($currentRole === null || in_array(self::normalizeRole($currentRole), [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN], true)) {
            $this->attributes['role'] = self::ROLE_USER;
        }

        unset($this->attributes['is_admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN], true);
    }

    public function isAdminPanelUser(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN
            || $this->role === self::ROLE_ADMIN;
    }

    public function canManageUsers(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canManageDealers(): bool
    {
        return $this->isAdmin();
    }

    public function isDealer(): bool
    {
        return $this->role === self::ROLE_DEALER;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
