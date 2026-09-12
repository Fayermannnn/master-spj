<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CostCategory;
use Illuminate\Database\Seeder;

/**
 * Baseline kategori biaya (§13 master prompt) — master data configurable.
 */
class CostCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'PERSONNEL', 'name' => 'Personil'],
            ['code' => 'NON_PERSONNEL', 'name' => 'Non-Personil'],
            ['code' => 'TRAVEL', 'name' => 'Perjalanan'],
            ['code' => 'ACCOMMODATION', 'name' => 'Akomodasi'],
            ['code' => 'TRANSPORT', 'name' => 'Transportasi'],
            ['code' => 'MEETING', 'name' => 'Rapat'],
            ['code' => 'SURVEY', 'name' => 'Survey'],
            ['code' => 'EQUIPMENT', 'name' => 'Peralatan'],
            ['code' => 'DOCUMENTATION', 'name' => 'Dokumentasi'],
            ['code' => 'PRINTING', 'name' => 'Pencetakan'],
            ['code' => 'OPERATIONAL', 'name' => 'Operasional'],
            ['code' => 'OTHER', 'name' => 'Lainnya'],
        ];

        foreach ($categories as $category) {
            CostCategory::query()->firstOrCreate(
                ['code' => $category['code']],
                ['name' => $category['name'], 'is_active' => true]
            );
        }
    }
}
