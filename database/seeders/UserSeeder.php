<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Identity\Enums\RoleName;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * User demo untuk setiap role, guna pengujian RBAC & UI.
 * Kredensial ini HANYA untuk lingkungan lokal/demo — lihat SETUP.md.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $demoOrganization = Organization::query()->where('code', 'DEMO-KONSULTAN')->firstOrFail();

        $superAdmin = User::query()->firstOrCreate(
            ['email' => 'superadmin@master-spj.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'organization_id' => null,
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->syncRoles([RoleName::SuperAdmin->value]);

        $adminPerusahaan = User::query()->firstOrCreate(
            ['email' => 'admin@ciptarencana.example'],
            [
                'name' => 'Admin Perusahaan Demo',
                'password' => Hash::make('password'),
                'organization_id' => $demoOrganization->id,
                'email_verified_at' => now(),
            ]
        );
        $adminPerusahaan->syncRoles([RoleName::AdminPerusahaan->value]);

        $staff = User::query()->firstOrCreate(
            ['email' => 'staff@ciptarencana.example'],
            [
                'name' => 'Staff Demo',
                'password' => Hash::make('password'),
                'organization_id' => $demoOrganization->id,
                'email_verified_at' => now(),
            ]
        );
        $staff->syncRoles([RoleName::Staff->value]);
    }
}
