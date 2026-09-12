<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProjectType;
use Illuminate\Database\Seeder;

/**
 * Baseline jenis project (master data, RULE 40: configuration over code).
 * Administrator dapat menambah jenis baru lewat UI — daftar ini hanya
 * starting point, bukan daftar tertutup.
 */
class ProjectTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'CONSULTANCY_PLANNING', 'name' => 'Konsultansi Perencanaan'],
            ['code' => 'CONSULTANCY_SUPERVISION', 'name' => 'Konsultansi Pengawasan'],
            ['code' => 'SURVEY', 'name' => 'Survey'],
            ['code' => 'STUDY', 'name' => 'Studi/Kajian'],
            ['code' => 'DOCUMENT_PREPARATION', 'name' => 'Penyusunan Dokumen'],
            ['code' => 'IT_DEVELOPMENT', 'name' => 'Sistem Informasi'],
            ['code' => 'NON_CONSTRUCTION', 'name' => 'Jasa Non-Konstruksi'],
            ['code' => 'CONSTRUCTION', 'name' => 'Jasa Konstruksi'],
        ];

        foreach ($types as $type) {
            ProjectType::query()->firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'is_active' => true]
            );
        }
    }
}
