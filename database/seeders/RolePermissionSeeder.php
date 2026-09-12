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
            PermissionName::ClientsViewAny->value,
            PermissionName::ClientsView->value,
            PermissionName::ClientsCreate->value,
            PermissionName::ClientsUpdate->value,
            PermissionName::ClientsDelete->value,
            PermissionName::ProjectsViewAny->value,
            PermissionName::ProjectsView->value,
            PermissionName::ProjectsCreate->value,
            PermissionName::ProjectsUpdate->value,
            PermissionName::ProjectsDelete->value,
            PermissionName::ProjectsTransitionStatus->value,
            PermissionName::PersonnelViewAny->value,
            PermissionName::PersonnelView->value,
            PermissionName::PersonnelCreate->value,
            PermissionName::PersonnelUpdate->value,
            PermissionName::PersonnelDelete->value,
            PermissionName::PersonnelAssignmentsManage->value,
        ]);

        // personnel.create/update/delete SENGAJA tidak diberikan ke
        // project_admin: mengelola roster personel perusahaan adalah
        // tanggung jawab admin_perusahaan (§7 master prompt). project_admin
        // hanya mengelola PENUGASAN personel ke project yang mereka kelola.
        $projectAdmin = Role::query()->firstOrCreate(['name' => RoleName::ProjectAdmin->value, 'guard_name' => 'web']);
        $projectAdmin->syncPermissions([
            PermissionName::UsersViewAny->value,
            PermissionName::UsersView->value,
            PermissionName::ClientsViewAny->value,
            PermissionName::ClientsView->value,
            PermissionName::ProjectsViewAny->value,
            PermissionName::ProjectsView->value,
            PermissionName::ProjectsUpdate->value,
            PermissionName::ProjectsTransitionStatus->value,
            PermissionName::PersonnelViewAny->value,
            PermissionName::PersonnelView->value,
            PermissionName::PersonnelAssignmentsManage->value,
        ]);

        $staff = Role::query()->firstOrCreate(['name' => RoleName::Staff->value, 'guard_name' => 'web']);
        $staff->syncPermissions([
            PermissionName::ClientsViewAny->value,
            PermissionName::ClientsView->value,
            PermissionName::ProjectsViewAny->value,
            PermissionName::ProjectsView->value,
            PermissionName::PersonnelViewAny->value,
            PermissionName::PersonnelView->value,
        ]);

        $viewer = Role::query()->firstOrCreate(['name' => RoleName::Viewer->value, 'guard_name' => 'web']);
        $viewer->syncPermissions([
            PermissionName::ClientsViewAny->value,
            PermissionName::ClientsView->value,
            PermissionName::ProjectsViewAny->value,
            PermissionName::ProjectsView->value,
            PermissionName::PersonnelViewAny->value,
            PermissionName::PersonnelView->value,
        ]);
    }
}
