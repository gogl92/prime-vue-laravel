<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view invoices',
            'create invoices',
            'edit invoices',
            'delete invoices',
            'view products',
            'create products',
            'edit products',
            'delete products',
            'view payments',
            'create payments',
            'edit payments',
            'delete payments',
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // User role - basic permissions
        $userRole = Role::create(['name' => 'user']);
        $userRole->givePermissionTo([
            'view invoices',
            'view products',
            'view payments',
        ]);

        // Admin role - most permissions except user management
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'view invoices',
            'create invoices',
            'edit invoices',
            'delete invoices',
            'view products',
            'create products',
            'edit products',
            'delete products',
            'view payments',
            'create payments',
            'edit payments',
            'delete payments',
        ]);

        // Super Admin role - all permissions
        $superAdminRole = Role::create(['name' => 'super_admin']);
        $superAdminRole->givePermissionTo(Permission::all());
    }
}
