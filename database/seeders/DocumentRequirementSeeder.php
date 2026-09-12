<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\DocumentRequirement\Enums\RequirementRuleField;
use App\Domain\DocumentRequirement\Enums\RequirementRuleOperator;
use App\Models\DocumentRequirement;
use Illuminate\Database\Seeder;

/**
 * Baseline document requirement (§17 master prompt) — universal (semua
 * jenis project) kecuali dicatat lain. Beberapa dilengkapi rule kondisional
 * (§18) sebagai contoh nyata Document Requirement Engine bekerja, BUKAN
 * daftar tertutup — admin dapat menambah/mengubah lewat UI.
 */
class DocumentRequirementSeeder extends Seeder
{
    public function run(): void
    {
        $unconditional = [
            ['code' => 'KONTRAK', 'name' => 'Kontrak', 'category' => 'Administrasi', 'sort_order' => 10],
            ['code' => 'SPMK', 'name' => 'SPMK', 'category' => 'Administrasi', 'sort_order' => 20],
            ['code' => 'SURAT_PERNYATAAN', 'name' => 'Surat Pernyataan', 'category' => 'Administrasi', 'sort_order' => 30],
            ['code' => 'INVOICE', 'name' => 'Invoice', 'category' => 'Keuangan', 'sort_order' => 40],
            ['code' => 'KWITANSI', 'name' => 'Kwitansi', 'category' => 'Keuangan', 'sort_order' => 50],
            ['code' => 'FAKTUR_PAJAK', 'name' => 'Faktur Pajak', 'category' => 'Keuangan', 'sort_order' => 60],
            ['code' => 'BERITA_ACARA', 'name' => 'Berita Acara', 'category' => 'Administrasi', 'sort_order' => 70],
            ['code' => 'LAPORAN', 'name' => 'Laporan', 'category' => 'Output', 'sort_order' => 80],
            ['code' => 'DOKUMENTASI', 'name' => 'Dokumentasi', 'category' => 'Output', 'sort_order' => 90],
        ];

        foreach ($unconditional as $requirement) {
            DocumentRequirement::query()->firstOrCreate(
                ['code' => $requirement['code']],
                [
                    'project_type_id' => null,
                    'name' => $requirement['name'],
                    'category' => $requirement['category'],
                    'is_active' => true,
                    'sort_order' => $requirement['sort_order'],
                ]
            );
        }

        $this->seedConditional(
            code: 'DAFTAR_PERSONEL',
            name: 'Daftar Personel',
            category: 'Personel',
            sortOrder: 100,
            field: RequirementRuleField::HasPersonnelAssignments,
            operator: RequirementRuleOperator::IsTrue,
        );

        $this->seedConditional(
            code: 'TIMESHEET',
            name: 'Timesheet',
            category: 'Personel',
            sortOrder: 110,
            field: RequirementRuleField::HasPersonnelAssignments,
            operator: RequirementRuleOperator::IsTrue,
        );

        $this->seedConditional(
            code: 'BUKTI_PERJALANAN',
            name: 'Bukti Perjalanan',
            category: 'Keuangan',
            sortOrder: 120,
            field: RequirementRuleField::HasTravelCost,
            operator: RequirementRuleOperator::IsTrue,
        );
    }

    private function seedConditional(
        string $code,
        string $name,
        string $category,
        int $sortOrder,
        RequirementRuleField $field,
        RequirementRuleOperator $operator,
    ): void {
        $requirement = DocumentRequirement::query()->firstOrCreate(
            ['code' => $code],
            [
                'project_type_id' => null,
                'name' => $name,
                'category' => $category,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]
        );

        $requirement->rules()->firstOrCreate([
            'field' => $field->value,
            'operator' => $operator->value,
        ], [
            'value' => null,
            'is_active' => true,
        ]);
    }
}
