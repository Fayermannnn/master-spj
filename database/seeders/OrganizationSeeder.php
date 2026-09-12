<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

/**
 * Organisasi demo (bukan konstanta aplikasi) — mewakili perusahaan
 * konsultan pengguna sistem, dipakai untuk seeding & pengujian modul lain.
 */
class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::query()->firstOrCreate(
            ['code' => 'DEMO-KONSULTAN'],
            [
                'name' => 'PT Cipta Rencana Konsultan',
                'npwp' => '01.234.567.8-901.000',
                'address' => 'Jl. Contoh Raya No. 1, Samarinda, Kalimantan Timur',
                'phone' => '0541-1234567',
                'email' => 'admin@ciptarencana.example',
                'is_active' => true,
            ]
        );
    }
}
