<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        Language::query()->updateOrCreate(
            ['code' => 'pt_BR'],
            ['name' => 'Português (Brasil)', 'flag' => '🇧🇷', 'is_active' => true, 'is_default' => true]
        );

        Plan::query()->updateOrCreate(
            ['slug' => 'basico'],
            [
                'name' => 'Básico',
                'price_monthly' => 29.90,
                'features_json' => [
                    'max_branches' => 2,
                    'delivery_webhooks' => false,
                    'kds' => true,
                    'reports' => false,
                    'pos' => false,
                    'motoboys' => false,
                ],
                'is_active' => true,
            ]
        );

        Plan::query()->updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'price_monthly' => 199.90,
                'features_json' => [
                    'max_branches' => 5,
                    'delivery_webhooks' => true,
                    'kds' => true,
                    'reports' => true,
                    'pos' => true,
                    'motoboys' => true,
                ],
                'is_active' => true,
            ]
        );

        Plan::query()->updateOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'price_monthly' => 399.90,
                'features_json' => [
                    'max_branches' => 20,
                    'delivery_webhooks' => true,
                    'kds' => true,
                    'reports' => true,
                    'pos' => true,
                    'motoboys' => true,
                ],
                'is_active' => true,
            ]
        );

        $permissions = [
            'products.manage', 'orders.view', 'orders.accept', 'orders.cancel', 'orders.print', 'orders.pos',
            'deliveries.update', 'coupons.manage', 'reports.view', 'kds.access', 'users.manage',
            'requests.view', 'requests.close', 'branches.manage', 'tenants.manage', 'platform.access',
            'chat.access',
            'ratings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $roles = [
            'superadmin' => $permissions,
            'tenant_admin' => array_diff($permissions, ['platform.access', 'tenants.manage']),
            'manager' => [
                'products.manage', 'orders.view', 'orders.accept', 'orders.cancel', 'orders.print', 'orders.pos',
                'deliveries.update', 'coupons.manage', 'reports.view', 'kds.access', 'requests.view', 'requests.close', 'branches.manage', 'chat.access', 'ratings.manage',
            ],
            'operator' => ['orders.view', 'orders.accept', 'orders.print', 'orders.pos', 'deliveries.update', 'kds.access', 'chat.access'],
            'viewer' => ['orders.view', 'reports.view', 'requests.view', 'ratings.manage'],
            'branch_staff' => [
                'orders.view', 'orders.accept', 'orders.print', 'kds.access', 'chat.access', 'requests.view',
            ],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::findOrCreate($roleName);
            $role->syncPermissions($perms);
        }

        $superadmin = User::query()->updateOrCreate(
            ['email' => env('SEED_SUPERADMIN_EMAIL', 'admin@admin.com.br')],
            [
                'name' => 'Super Admin',
                'password' => Hash::make(env('SEED_SUPERADMIN_PASSWORD', 'Mudar123@')),
                'tenant_id' => null,
                'is_platform_user' => true,
                'is_protected_admin' => false,
                'email_verified_at' => now(),
            ]
        );

        $superadmin->syncRoles(['superadmin']);

        $this->command->info('Platform seeded. Superadmin: '.env('SEED_SUPERADMIN_EMAIL', 'admin@admin.com.br'));
    }
}
