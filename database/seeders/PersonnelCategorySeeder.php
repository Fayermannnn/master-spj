<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PersonnelCategory;
use Illuminate\Database\Seeder;

/**
 * Baseline kategori personel (§11 master prompt) — master data global,
 * dapat ditambah admin. Bukan daftar tertutup.
 */
class PersonnelCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'TENAGA_AHLI', 'name' => 'Tenaga Ahli'],
            ['code' => 'TENAGA_PENDUKUNG', 'name' => 'Tenaga Pendukung'],
            ['code' => 'SURVEYOR', 'name' => 'Surveyor'],
            ['code' => 'OPERATOR', 'name' => 'Operator'],
            ['code' => 'ADMINISTRASI', 'name' => 'Administrasi'],
        ];

        foreach ($categories as $category) {
            PersonnelCategory::query()->firstOrCreate(
                ['code' => $category['code']],
                ['name' => $category['name'], 'is_active' => true]
            );
        }
    }
}
