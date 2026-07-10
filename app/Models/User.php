<?php

namespace App\Models;

use App\Notifications\ImmediateResetPassword;
use App\Notifications\ImmediateVerifyEmail;
use App\Support\EmailVerificationCode;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements MustVerifyEmail, HasLocalePreference
{
    use HasApiTokens, HasFactory, LogsActivity, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_PRODUCT_MANAGER = 'product_manager';
    public const ROLE_ORDER_MANAGER = 'order_manager';
    public const ROLE_FINANCE_MANAGER = 'finance_manager';
    public const ROLE_INVENTORY_MANAGER = 'inventory_manager';
    public const ROLE_SETTINGS_MANAGER = 'settings_manager';
    public const ROLE_USER = 'user';
    public const ROLE_DEALER = 'dealer';
    public const PERMISSION_DASHBOARD_VIEW = 'dashboard.view';
    public const PERMISSION_PRODUCTS_MANAGE = 'products.manage';
    public const PERMISSION_ORDERS_MANAGE = 'orders.manage';
    public const PERMISSION_FINANCE_VIEW = 'finance.view';
    public const PERMISSION_FINANCE_MANAGE = 'finance.manage';
    public const PERMISSION_SETTINGS_MANAGE = 'settings.manage';
    public const PERMISSION_STOCK_MANAGE = 'stock.manage';
    public const PERMISSION_USERS_VIEW = 'users.view';
    public const PERMISSION_USERS_MANAGE = 'users.manage';
    public const PERMISSION_DEALERS_MANAGE = 'dealers.manage';
    public const PERMISSION_ACTIVITY_LOGS_VIEW = 'activity_logs.view';
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
        'phone_normalized',
        'date_of_birth',
        'profile_photo_path',
        'theme_preference',
        'locale_preference',
        'notify_order_updates',
        'notify_promotions',
        'notify_stock_alerts',
        'two_factor_preference',
        'login_alerts',
        'session_timeout',
        'email_notifications',
        'sms_notifications',
        'whatsapp_notifications',
        'marketing_consent',
        'currency_preference',
        'timezone_preference',
        'date_format_preference',
        'default_contact_method',
        'default_delivery_note',
        'express_checkout',
        'font_size_preference',
        'reduced_motion',
        'high_contrast_mode',
    ];

    /**
     * Privilege/role fields intentionally excluded from $fillable to block
     * mass-assignment escalation. Set these only via explicit forceFill()->save()
     * in admin-gated controllers after validating against allowlists.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'role',
        'permissions',
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
        'notify_order_updates' => 'boolean',
        'notify_promotions' => 'boolean',
        'notify_stock_alerts' => 'boolean',
        'login_alerts' => 'boolean',
        'email_notifications' => 'boolean',
        'sms_notifications' => 'boolean',
        'whatsapp_notifications' => 'boolean',
        'marketing_consent' => 'boolean',
        'express_checkout' => 'boolean',
        'reduced_motion' => 'boolean',
        'high_contrast_mode' => 'boolean',
        'dealer_discount' => 'decimal:2',
        'date_of_birth' => 'date',
        'permissions' => 'array',
    ];

    protected function themePreference(): Attribute
    {
        return Attribute::make(
            get: static fn ($value): string => in_array($value, ['light', 'dark'], true) ? $value : 'light',
            set: static fn ($value): string => in_array($value, ['light', 'dark'], true) ? $value : 'light',
        );
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (empty($user->attributes['role'])) {
                $user->attributes['role'] = self::ROLE_USER;
            }
        });
    }

    /**
     * Activity log scope is deliberately narrow: only the security/finance-
     * relevant attributes are recorded so that profile/preference changes do
     * not flood the log. Password, remember_token, and PII like phone and
     * date_of_birth are intentionally NOT logged.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'role',
                'permissions',
                'dealer_status',
                'dealer_discount',
                'email',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new ImmediateVerifyEmail(EmailVerificationCode::generateFor($this)));
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ImmediateResetPassword($token));
    }

    public function preferredLocale(): string
    {
        $locale = (string) ($this->locale_preference ?: app()->getLocale());

        return in_array($locale, ['en', 'ar', 'ku'], true) ? $locale : 'en';
    }

    public static function allowedRoles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_PRODUCT_MANAGER,
            self::ROLE_ORDER_MANAGER,
            self::ROLE_FINANCE_MANAGER,
            self::ROLE_INVENTORY_MANAGER,
            self::ROLE_SETTINGS_MANAGER,
            self::ROLE_DEALER,
            self::ROLE_USER,
        ];
    }

    public static function permissionGroups(): array
    {
        return [
            __('General') => [
                self::PERMISSION_DASHBOARD_VIEW => __('View admin dashboard'),
            ],
            __('Products') => [
                self::PERMISSION_PRODUCTS_MANAGE => __('Manage products, categories, and vehicle fitments'),
            ],
            __('Orders') => [
                self::PERMISSION_ORDERS_MANAGE => __('Manage orders, invoices, returns, and order notes'),
            ],
            __('Finance') => [
                self::PERMISSION_FINANCE_VIEW => __('View revenue reports'),
                self::PERMISSION_FINANCE_MANAGE => __('Manage coupons and discount rules'),
            ],
            __('Stock') => [
                self::PERMISSION_STOCK_MANAGE => __('Manage inventory movements and stock alerts'),
            ],
            __('Settings') => [
                self::PERMISSION_SETTINGS_MANAGE => __('Manage system settings'),
            ],
            __('Users') => [
                self::PERMISSION_USERS_VIEW => __('View users'),
                self::PERMISSION_USERS_MANAGE => __('Manage users, passwords, roles, and permissions'),
                self::PERMISSION_DEALERS_MANAGE => __('Manage dealer accounts'),
            ],
            __('Audit') => [
                self::PERMISSION_ACTIVITY_LOGS_VIEW => __('View admin activity logs'),
            ],
        ];
    }

    public static function allowedPermissions(): array
    {
        return collect(self::permissionGroups())
            ->flatMap(fn (array $permissions) => array_keys($permissions))
            ->values()
            ->all();
    }

    public static function normalizePermissions(?array $permissions): array
    {
        $allowed = self::allowedPermissions();

        $normalized = collect($permissions ?? [])
            ->map(fn ($permission) => trim((string) $permission))
            ->filter(fn (string $permission) => in_array($permission, $allowed, true))
            ->unique()
            ->values()
            ->all();

        if ($normalized !== [] && !in_array(self::PERMISSION_DASHBOARD_VIEW, $normalized, true)) {
            array_unshift($normalized, self::PERMISSION_DASHBOARD_VIEW);
        }

        return $normalized;
    }

    public static function defaultPermissionsForRole(?string $role): array
    {
        $role = self::normalizeRole($role);

        return match ($role) {
            self::ROLE_SUPER_ADMIN => self::allowedPermissions(),
            self::ROLE_ADMIN => [
                self::PERMISSION_DASHBOARD_VIEW,
                self::PERMISSION_PRODUCTS_MANAGE,
                self::PERMISSION_ORDERS_MANAGE,
                self::PERMISSION_FINANCE_VIEW,
                self::PERMISSION_FINANCE_MANAGE,
                self::PERMISSION_SETTINGS_MANAGE,
                self::PERMISSION_STOCK_MANAGE,
                self::PERMISSION_USERS_VIEW,
                self::PERMISSION_DEALERS_MANAGE,
            ],
            self::ROLE_PRODUCT_MANAGER => [
                self::PERMISSION_DASHBOARD_VIEW,
                self::PERMISSION_PRODUCTS_MANAGE,
            ],
            self::ROLE_ORDER_MANAGER => [
                self::PERMISSION_DASHBOARD_VIEW,
                self::PERMISSION_ORDERS_MANAGE,
            ],
            self::ROLE_FINANCE_MANAGER => [
                self::PERMISSION_DASHBOARD_VIEW,
                self::PERMISSION_FINANCE_VIEW,
                self::PERMISSION_FINANCE_MANAGE,
            ],
            self::ROLE_INVENTORY_MANAGER => [
                self::PERMISSION_DASHBOARD_VIEW,
                self::PERMISSION_STOCK_MANAGE,
            ],
            self::ROLE_SETTINGS_MANAGER => [
                self::PERMISSION_DASHBOARD_VIEW,
                self::PERMISSION_SETTINGS_MANAGE,
            ],
            default => [],
        };
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
            'product-manager', 'products_manager', 'product manager' => self::ROLE_PRODUCT_MANAGER,
            'order-manager', 'orders_manager', 'order manager' => self::ROLE_ORDER_MANAGER,
            'finance-manager', 'accountant', 'finance manager' => self::ROLE_FINANCE_MANAGER,
            'inventory-manager', 'stock-manager', 'inventory manager', 'stock manager' => self::ROLE_INVENTORY_MANAGER,
            'settings-manager', 'settings manager' => self::ROLE_SETTINGS_MANAGER,
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

    public function setPhoneAttribute(?string $value): void
    {
        $phone = $value !== null ? trim($value) : null;
        $this->attributes['phone'] = $phone !== '' ? $phone : null;
        $this->attributes['phone_normalized'] = self::normalizePhone($phone);
    }

    public static function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        $phone = strtr($phone, [
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
        ]);

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return $digits !== '' ? $digits : null;
    }

    public static function uniquePhoneRule(?int $ignoreUserId = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($ignoreUserId): void {
            $normalized = self::normalizePhone(is_scalar($value) ? (string) $value : null);

            if ($normalized === null) {
                return;
            }

            $query = self::query()->where('phone_normalized', $normalized);

            if ($ignoreUserId !== null) {
                $query->whereKeyNot($ignoreUserId);
            }

            if ($query->exists()) {
                $fail(__('This phone number is already registered.'));
            }
        };
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
        return $this->hasAnyPermission(self::allowedPermissions());
    }

    public function canManageUsers(): bool
    {
        return $this->hasPermission(self::PERMISSION_USERS_MANAGE);
    }

    public function canManageDealers(): bool
    {
        return $this->hasPermission(self::PERMISSION_DEALERS_MANAGE);
    }

    public function effectivePermissions(): array
    {
        if ($this->isSuperAdmin()) {
            return self::allowedPermissions();
        }

        $storedPermissions = $this->getAttribute('permissions');

        if (is_array($storedPermissions)) {
            return self::normalizePermissions($storedPermissions);
        }

        return self::defaultPermissionsForRole($this->role);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->effectivePermissions(), true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return count(array_intersect($permissions, $this->effectivePermissions())) > 0;
    }

    public function isDealer(): bool
    {
        return $this->role === self::ROLE_DEALER;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function productReviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function productViews()
    {
        return $this->hasMany(ProductView::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }
}
