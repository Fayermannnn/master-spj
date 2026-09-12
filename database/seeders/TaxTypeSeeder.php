<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TaxType;
use Illuminate\Database\Seeder;

/**
 * Baseline jenis pajak (§59 master prompt) — master data configurable,
 * admin dapat menambah/mengubah tarif. Nilai di sini bukan nasihat pajak
 * (§60 master prompt: sistem tidak mengklaim kepatuhan pajak otomatis).
 */
class TaxTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'PPN', 'name' => 'PPN', 'rate' => 11.00],
            ['code' => 'PPH21', 'name' => 'PPh 21', 'rate' => 5.00],
            ['code' => 'PPH23', 'name' => 'PPh 23', 'rate' => 2.00],
            ['code' => 'NON_TAXABLE', 'name' => 'Tidak Kena Pajak', 'rate' => 0.00],
        ];

        foreach ($types as $type) {
            TaxType::query()->firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'rate' => $type['rate'], 'is_active' => true]
            );
        }
    }
}
