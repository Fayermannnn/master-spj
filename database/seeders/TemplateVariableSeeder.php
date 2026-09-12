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
            ['key' => 'cost_item.description', 'label' => 'Uraian Item Biaya', 'type' => TemplateVariableDataType::Table],
            ['key' => 'cost_item.quantity', 'label' => 'Kuantitas Item Biaya', 'type' => TemplateVariableDataType::Table],
            ['key' => 'cost_item.unit', 'label' => 'Satuan Item Biaya', 'type' => TemplateVariableDataType::Table],
            ['key' => 'cost_item.unit_price', 'label' => 'Harga Satuan Item Biaya', 'type' => TemplateVariableDataType::Table],
            ['key' => 'cost_item.subtotal', 'label' => 'Subtotal Item Biaya', 'type' => TemplateVariableDataType::Table],
            ['key' => 'cost_item.tax_amount', 'label' => 'Pajak Item Biaya', 'type' => TemplateVariableDataType::Table],
            ['key' => 'cost_item.total', 'label' => 'Total Item Biaya', 'type' => TemplateVariableDataType::Table],
            ['key' => 'payment.amount', 'label' => 'Nominal Pembayaran', 'type' => TemplateVariableDataType::Currency],
            ['key' => 'payment.amount_terbilang', 'label' => 'Nominal Pembayaran (Terbilang)', 'type' => TemplateVariableDataType::Text],
            ['key' => 'payment.termin', 'label' => 'Nomor Termin', 'type' => TemplateVariableDataType::Number],
            ['key' => 'payment.date', 'label' => 'Tanggal Pembayaran', 'type' => TemplateVariableDataType::Date],
            ['key' => 'payment.name', 'label' => 'Nama Termin', 'type' => TemplateVariableDataType::Text],
            ['key' => 'payment.percentage', 'label' => 'Persentase Termin', 'type' => TemplateVariableDataType::Text],
            ['key' => 'payment.trigger', 'label' => 'Pemicu Pembayaran', 'type' => TemplateVariableDataType::Text],
            ['key' => 'deliverable.name', 'label' => 'Nama Output/Deliverable', 'type' => TemplateVariableDataType::Text],
            ['key' => 'deliverable.target_date', 'label' => 'Tanggal Target Deliverable', 'type' => TemplateVariableDataType::Date],
            ['key' => 'salary.personnel_name', 'label' => 'Nama Personel (Slip Gaji)', 'type' => TemplateVariableDataType::Text],
            ['key' => 'salary.personnel_position', 'label' => 'Posisi Personel (Slip Gaji)', 'type' => TemplateVariableDataType::Text],
            ['key' => 'salary.personnel_npwp', 'label' => 'NPWP Personel (Slip Gaji)', 'type' => TemplateVariableDataType::Text],
            ['key' => 'salary.description', 'label' => 'Uraian Honor', 'type' => TemplateVariableDataType::Text],
            ['key' => 'salary.quantity', 'label' => 'Kuantitas Honor', 'type' => TemplateVariableDataType::Text],
            ['key' => 'salary.unit', 'label' => 'Satuan Honor', 'type' => TemplateVariableDataType::Text],
            ['key' => 'salary.unit_price', 'label' => 'Tarif Satuan Honor', 'type' => TemplateVariableDataType::Currency],
            ['key' => 'salary.subtotal', 'label' => 'Subtotal Honor', 'type' => TemplateVariableDataType::Currency],
            ['key' => 'salary.tax_amount', 'label' => 'Pajak Honor', 'type' => TemplateVariableDataType::Currency],
            ['key' => 'salary.total', 'label' => 'Total Honor', 'type' => TemplateVariableDataType::Currency],
            ['key' => 'salary.amount_terbilang', 'label' => 'Total Honor (Terbilang)', 'type' => TemplateVariableDataType::Text],
            ['key' => 'salary.period_start', 'label' => 'Awal Periode Penugasan', 'type' => TemplateVariableDataType::Date],
            ['key' => 'salary.period_end', 'label' => 'Akhir Periode Penugasan', 'type' => TemplateVariableDataType::Date],
            ['key' => 'travel.personnel_name', 'label' => 'Nama Personel (SPPD)', 'type' => TemplateVariableDataType::Text],
            ['key' => 'travel.personnel_position', 'label' => 'Posisi Personel (SPPD)', 'type' => TemplateVariableDataType::Text],
            ['key' => 'travel.destination', 'label' => 'Tujuan Perjalanan', 'type' => TemplateVariableDataType::Text],
            ['key' => 'travel.purpose', 'label' => 'Keperluan Perjalanan', 'type' => TemplateVariableDataType::Text],
            ['key' => 'travel.departure_date', 'label' => 'Tanggal Berangkat', 'type' => TemplateVariableDataType::Date],
            ['key' => 'travel.return_date', 'label' => 'Tanggal Kembali', 'type' => TemplateVariableDataType::Date],
            ['key' => 'travel.transportation_mode', 'label' => 'Moda Transportasi', 'type' => TemplateVariableDataType::Text],
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
