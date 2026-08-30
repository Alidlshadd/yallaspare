<?php

namespace App\Providers;

use App\Models\AdminActivityLog;
use App\Models\Goal;
use App\Models\User;
use App\Policies\AdminActivityLogPolicy;
use App\Policies\GoalPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        AdminActivityLog::class => AdminActivityLogPolicy::class,
        Activity::class => AdminActivityLogPolicy::class,
        Goal::class => GoalPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        foreach (User::allowedPermissions() as $permission) {
            Gate::define($permission, fn (User $user): bool => $user->hasPermission($permission));
        }

        Gate::define('stock-requests.manage', fn (User $user): bool => $user->hasAnyPermission([
            User::PERMISSION_STOCK_MANAGE,
            User::PERMISSION_PRODUCTS_MANAGE,
        ]));

        Gate::define('manage-users', [UserPolicy::class, 'manageUsers']);
        Gate::define('manage-dealers', [UserPolicy::class, 'manageDealers']);
        Gate::define('manage-whatsapp-webhooks', fn (User $user): bool => $user->isAdmin());
    }
}
