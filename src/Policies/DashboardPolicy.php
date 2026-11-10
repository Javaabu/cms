<?php

namespace Javaabu\Cms\Policies;

use Illuminate\Database\Eloquent\Model;

/**
 * Who sees the admin dashboard: users with view_dashboard. Override it with
 * config('cms.policies.dashboard'), config('permissions.policies') or
 * Gate::policy(Dashboard::class, …).
 */
class DashboardPolicy
{
    public function view(Model $user): bool
    {
        return $user->can('view_dashboard');
    }
}
