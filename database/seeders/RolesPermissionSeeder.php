<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionGroups = [
            'branches' => ['view', 'create', 'edit', 'delete'],
            'customers' => ['view', 'create', 'edit', 'delete'],
            'products' => ['view', 'create', 'edit', 'delete'],
            'equipment' => ['view', 'create', 'edit', 'delete'],
            'inventory' => ['view', 'edit', 'adjust', 'opname'],
            'schedules' => ['view', 'create', 'edit', 'delete'],
            'transactions' => ['view', 'create', 'void'],
            'discounts' => ['view', 'create', 'edit', 'delete'],
            'expenses' => ['view', 'create', 'edit', 'delete'],
            'payroll' => ['view', 'create', 'edit', 'approve'],
            'reports' => ['view', 'export'],
            'dashboard' => ['view'],
            'settings' => ['view', 'edit'],
            'notifications' => ['view'],
            'bookings' => ['view', 'create', 'edit', 'delete'],
            'users' => ['view', 'create', 'edit', 'delete'],
        ];

        $permissions = [];
        foreach ($permissionGroups as $module => $actions) {
            foreach ($actions as $action) {
                $permissions["$module.$action"] = Permission::updateOrCreate([
                    'name' => "$module.$action",
                    'guard_name' => 'web',
                ]);
            }
        }

        /** @var array<string, list<string>> $roleMatrix */
        $roleMatrix = [
            'owner' => array_keys($permissions),
            'admin-cabang' => [
                'branches.view', 'branches.create', 'branches.edit',
                'customers.view', 'customers.create', 'customers.edit',
                'products.view', 'products.create', 'products.edit',
                'equipment.view', 'equipment.create', 'equipment.edit',
                'inventory.view', 'inventory.edit',
                'schedules.view', 'schedules.create', 'schedules.edit',
                'transactions.view', 'transactions.create',
                'discounts.view', 'discounts.create',
                'expenses.view', 'expenses.create',
                'reports.view',
                'dashboard.view',
                'notifications.view',
                'bookings.view', 'bookings.create', 'bookings.edit',
                'users.view', 'users.create', 'users.edit',
            ],
            'kasir' => [
                'customers.view', 'customers.create', 'customers.edit',
                'products.view',
                'schedules.view',
                'transactions.view', 'transactions.create',
                'dashboard.view',
                'bookings.view', 'bookings.create', 'bookings.edit',
            ],
            'dive-guide' => [
                'schedules.view',
                'customers.view',
                'equipment.view',
            ],
            'finance' => [
                'branches.view',
                'expenses.view', 'expenses.create', 'expenses.edit',
                'payroll.view', 'payroll.create',
                'inventory.view', 'inventory.edit',
                'reports.view', 'reports.export',
                'dashboard.view',
                'notifications.view',
                'users.view',
            ],
            'marketing' => [
                'discounts.view', 'discounts.create', 'discounts.edit',
                'customers.view',
                'reports.view',
            ],
        ];

        foreach ($roleMatrix as $roleName => $permissionNames) {
            $role = Role::updateOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissionNames);
        }
    }
}
