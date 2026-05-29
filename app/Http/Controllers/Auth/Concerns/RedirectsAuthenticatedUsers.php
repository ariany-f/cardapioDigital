<?php

namespace App\Http\Controllers\Auth\Concerns;

use App\Models\User;

trait RedirectsAuthenticatedUsers
{
    protected function defaultAuthenticatedRedirect(User $user): string
    {
        if ($user->is_platform_user) {
            return route('platform.dashboard', absolute: false);
        }

        if ($user->tenant_id) {
            $slug = $user->relationLoaded('tenant')
                ? $user->tenant?->slug
                : $user->tenant()->value('slug');

            if ($slug) {
                return route('tenant.admin.dashboard', ['tenant' => $slug], absolute: false);
            }
        }

        return '/';
    }
}
