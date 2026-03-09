<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'user.view',
        'user.create',
        'user.update',
        'user.assign-role',
        'cupboard.view',
        'cupboard.create',
        'cupboard.update',
        'cupboard.delete',
        'place.view',
        'place.create',
        'place.update',
        'place.delete',
        'item.view',
        'item.create',
        'item.update',
        'item.delete',
        'item.adjust-quantity',
        'borrow.view',
        'borrow.create',
        'borrow.return',
        'audit.view',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $adminRole = Role::findOrCreate('Admin', 'web');
        $staffRole = Role::findOrCreate('Staff', 'web');

        $adminRole->syncPermissions(Permission::all());

        $staffRole->syncPermissions([
            'cupboard.view',
            'cupboard.create',
            'cupboard.update',
            'cupboard.delete',
            'place.view',
            'place.create',
            'place.update',
            'place.delete',
            'item.view',
            'item.create',
            'item.update',
            'item.delete',
            'item.adjust-quantity',
            'borrow.view',
            'borrow.create',
            'borrow.return',
        ]);
    }
}