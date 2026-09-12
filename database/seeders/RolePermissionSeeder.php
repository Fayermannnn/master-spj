<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Identity\Enums\PermissionName;
use App\Domain\Identity\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role & permission adalah master data (RULE 40: configuration over code).
 * Seeder ini hanya menjamin baseline minimum ada; admin dapat menambah role
 * atau permission baru lewat modul Settings di fase mendatang.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        $allPermissions = Permission::query()->pluck('name')->all();

        $superAdmin = Role::query()->firstOrCreate(['name' => RoleName::SuperAdmin->value, 'guard_name' => 'web']);
        $superAdmin->syncPermissions($allPermissions);

        // organizations.view / organizations.update SENGAJA tidak diberikan ke
        // admin_perusahaan: akses mereka ke organisasi diatur murni oleh
        // OrganizationPolicy (hanya organisasi sendiri), bukan permission
        // blanket — lihat App\Policies\OrganizationPolicy.
        $adminPerusahaan = Role::query()->firstOrCreate(['name' => RoleName::AdminPerusahaan->value, 'guard_name' => 'web']);
        $adminPerusahaan->syncPermissions([
            PermissionName::UsersViewAny->value,
            PermissionName::UsersView->value,
            PermissionName::UsersCreate->value,
            PermissionName::UsersUpdate->value,
            PermissionName::UsersDelete->value,
        ]);

        $projectAdmin = Role::query()->firstOrCreate(['name' => RoleName::ProjectAdmin->value, 'guard_name' => 'web']);
        $projectAdmin->syncPermissions([
            PermissionName::UsersViewAny->value,
            PermissionName::UsersView->value,
        ]);

        Role::query()->firstOrCreate(['name' => RoleName::Staff->value, 'guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => RoleName::Viewer->value, 'guard_name' => 'web']);
    }
}
