<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\DocumentTemplate\Enums\TemplateVariableDataType;
use App\Models\TemplateVariable;
use Illuminate\Database\Seeder;

/**
 * Baseline template variable (§20 master prompt) — master data
 * configurable, admin dapat menambah lewat UI. Bukan daftar tertutup.
 */
class TemplateVariableSeeder extends Seeder
{
    public function run(): void
    {
        $variables = [
            ['key' => 'project.name', 'label' => 'Nama Project', 'type' => TemplateVariableDataType::Text],
            ['key' => 'project.contract_number', 'label' => 'Nomor Kontrak', 'type' => TemplateVariableDataType::Text],
            ['key' => 'project.contract_date', 'label' => 'Tanggal Kontrak', 'type' => TemplateVariableDataType::Date],
            ['key' => 'project.contract_value', 'label' => 'Nilai Kontrak', 'type' => TemplateVariableDataType::Currency],
            ['key' => 'project.start_date', 'label' => 'Tanggal Mulai', 'type' => TemplateVariableDataType::Date],
            ['key' => 'project.end_date', 'label' => 'Tanggal Selesai', 'type' => TemplateVariableDataType::Date],
            ['key' => 'client.name', 'label' => 'Nama Klien/Instansi', 'type' => TemplateVariableDataType::Text],
            ['key' => 'client.address', 'label' => 'Alamat Klien/Instansi', 'type' => TemplateVariableDataType::Text],
            ['key' => 'ppk.name', 'label' => 'Nama PPK', 'type' => TemplateVariableDataType::Text],
            ['key' => 'provider.name', 'label' => 'Nama Penyedia', 'type' => TemplateVariableDataType::Text],
            ['key' => 'provider.address', 'label' => 'Alamat Penyedia', 'type' => TemplateVariableDataType::Text],
            ['key' => 'personnel.name', 'label' => 'Nama Personel', 'type' => TemplateVariableDataType::Table],
            ['key' => 'personnel.position', 'label' => 'Posisi Personel', 'type' => TemplateVariableDataType::Table],
            ['key' => 'personnel.npwp', 'label' => 'NPWP Personel', 'type' => TemplateVariableDataType::Table],
            ['key' => 'payment.amount', 'label' => 'Nominal Pembayaran', 'type' => TemplateVariableDataType::Currency],
            ['key' => 'payment.amount_terbilang', 'label' => 'Nominal Pembayaran (Terbilang)', 'type' => TemplateVariableDataType::Text],
            ['key' => 'payment.termin', 'label' => 'Nomor Termin', 'type' => TemplateVariableDataType::Number],
            ['key' => 'payment.date', 'label' => 'Tanggal Pembayaran', 'type' => TemplateVariableDataType::Date],
            ['key' => 'payment.name', 'label' => 'Nama Termin', 'type' => TemplateVariableDataType::Text],
            ['key' => 'payment.percentage', 'label' => 'Persentase Termin', 'type' => TemplateVariableDataType::Text],
            ['key' => 'payment.trigger', 'label' => 'Pemicu Pembayaran', 'type' => TemplateVariableDataType::Text],
            ['key' => 'deliverable.name', 'label' => 'Nama Output/Deliverable', 'type' => TemplateVariableDataType::Text],
            ['key' => 'deliverable.target_date', 'label' => 'Tanggal Target Deliverable', 'type' => TemplateVariableDataType::Date],
            ['key' => 'document.number', 'label' => 'Nomor Dokumen (Otomatis)', 'type' => TemplateVariableDataType::Text],
            ['key' => 'organization.logo', 'label' => 'Logo Organisasi (Kop Surat)', 'type' => TemplateVariableDataType::Image],
            ['key' => 'today', 'label' => 'Tanggal Hari Ini', 'type' => TemplateVariableDataType::Date],
        ];

        foreach ($variables as $variable) {
            TemplateVariable::query()->firstOrCreate(
                ['key' => $variable['key']],
                [
                    'label' => $variable['label'],
                    'data_type' => $variable['type']->value,
                    'is_active' => true,
                ]
            );
        }
    }
}
