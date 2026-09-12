<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Client\Enums\ContactType;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\ProjectManagement\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\CostCategory;
use App\Models\Organization;
use App\Models\Personnel;
use App\Models\PersonnelCategory;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\TaxType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Reference/sample project berdasarkan KAK "Revisi Final KAK Jasa
 * Konsultansi Perencanaan Master Plan RSPNDD" — dipakai HANYA sebagai
 * data seed/demo untuk menguji desain sistem (lihat PROJECT_BLUEPRINT.md
 * §3). Nomor kontrak/SPMK di bawah adalah nilai contoh, bukan kutipan
 * dokumen asli (tidak disebutkan literal di KAK).
 */
class ReferenceProjectSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->where('code', 'DEMO-KONSULTAN')->firstOrFail();

        $client = Client::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Pemerintah Kabupaten Mahakam Ulu'],
            [
                'address' => 'Ujoh Bilang, Kabupaten Mahakam Ulu, Kalimantan Timur',
                'is_active' => true,
            ]
        );

        $ppk = Contact::query()->firstOrCreate(
            ['client_id' => $client->id, 'type' => ContactType::Ppk->value, 'name' => 'dr. Josimar Hagusvaro Sinaga'],
            ['position' => 'Pejabat Pembuat Komitmen (PPK)']
        );

        $projectType = ProjectType::query()->where('code', 'CONSULTANCY_PLANNING')->firstOrFail();

        $startDate = Carbon::create(2026, 2, 1);
        $durationDays = 110;

        $project = Project::query()->firstOrCreate(
            ['code' => 'RSPNDD-MASTERPLAN-2026'],
            [
                'organization_id' => $organization->id,
                'project_type_id' => $projectType->id,
                'client_id' => $client->id,
                'ppk_contact_id' => $ppk->id,
                'name' => 'Jasa Konsultansi Perencanaan Master Plan Rumah Sakit Pratama Nawacita Datah Dave',
                'unit_work' => 'Rumah Sakit Pratama Nawacita Datah Dave',
                'project_manager_name' => null,
                'start_date' => $startDate,
                'end_date' => $startDate->clone()->addDays($durationDays),
                'duration_days' => $durationDays,
                'status' => ProjectStatus::Preparation->value,
                'description' => 'Lingkup: Feasibility Study, Master Plan, Konsepsi Perancangan, DED Blok Plan.',
                'notes' => null,
            ]
        );

        $ppn = TaxType::query()->where('code', 'PPN')->firstOrFail();

        Contract::query()->firstOrCreate(
            ['project_id' => $project->id],
            [
                'contract_number' => '027/KTR-KONSULTANSI/RSPNDD/2026',
                'contract_date' => $startDate,
                'spmk_number' => '028/SPMK/RSPNDD/2026',
                'spmk_date' => $startDate,
                'contract_value' => 1_980_610_920.00,
                'tax_type_id' => $ppn->id,
                'tax_amount' => round(1_980_610_920.00 * 11 / 111, 2),
                'net_value' => round(1_980_610_920.00 * 100 / 111, 2),
                'notes' => 'Reference/sample project — lihat PROJECT_BLUEPRINT.md §3.',
            ]
        );

        $this->seedPersonnel($organization, $project);
        $this->seedCostAndPayment($project);
    }

    /**
     * Sebagian kecil dari ~20 posisi tenaga ahli/pendukung pada KAK asli
     * (§12 master prompt) — cukup untuk mendemonstrasikan penugasan
     * personel, bukan replikasi lengkap seluruh struktur tim KAK.
     */
    private function seedPersonnel(Organization $organization, Project $project): void
    {
        $tenagaAhli = PersonnelCategory::query()->where('code', 'TENAGA_AHLI')->firstOrFail();

        $ketuaTim = Personnel::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Ir. Bambang Wicaksono, M.T.'],
            [
                'personnel_category_id' => $tenagaAhli->id,
                'position' => 'Ketua Tim',
                'education' => 'S2 Teknik Arsitektur',
                'default_rate' => 45_320_000,
                'is_active' => true,
            ]
        );

        $ahliArsitektur = Personnel::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Dewi Anggraini, S.T., M.Ars.'],
            [
                'personnel_category_id' => $tenagaAhli->id,
                'position' => 'Tenaga Ahli Teknik Arsitektur',
                'education' => 'S2 Arsitektur',
                'default_rate' => 32_500_000,
                'is_active' => true,
            ]
        );

        $project->update(['project_manager_personnel_id' => $ketuaTim->id]);

        $project->personnelAssignments()->firstOrCreate(
            ['personnel_id' => $ketuaTim->id],
            [
                'role_on_project' => 'Ketua Tim',
                'quantity' => 4,
                'unit' => 'OB',
                'unit_price' => $ketuaTim->default_rate,
                'subtotal' => 4 * (float) $ketuaTim->default_rate,
            ]
        );

        $project->personnelAssignments()->firstOrCreate(
            ['personnel_id' => $ahliArsitektur->id],
            [
                'role_on_project' => 'Tenaga Ahli Teknik Arsitektur',
                'quantity' => 3,
                'unit' => 'OB',
                'unit_price' => $ahliArsitektur->default_rate,
                'subtotal' => 3 * (float) $ahliArsitektur->default_rate,
            ]
        );
    }

    private function seedCostAndPayment(Project $project): void
    {
        $travel = CostCategory::query()->where('code', 'TRAVEL')->firstOrFail();
        $printing = CostCategory::query()->where('code', 'PRINTING')->firstOrFail();

        $project->costItems()->firstOrCreate(
            ['project_id' => $project->id, 'description' => 'Perjalanan Survey Lapangan ke Mahakam Ulu'],
            [
                'cost_category_id' => $travel->id,
                'quantity' => 2,
                'unit' => 'Paket',
                'unit_price' => 15_000_000,
                'is_tax_inclusive' => false,
                'subtotal' => 30_000_000,
                'tax_amount' => 0,
                'total' => 30_000_000,
            ]
        );

        $project->costItems()->firstOrCreate(
            ['project_id' => $project->id, 'description' => 'Pencetakan & Penjilidan Laporan'],
            [
                'cost_category_id' => $printing->id,
                'quantity' => 10,
                'unit' => 'Set',
                'unit_price' => 350_000,
                'is_tax_inclusive' => false,
                'subtotal' => 3_500_000,
                'tax_amount' => 0,
                'total' => 3_500_000,
            ]
        );

        $project->payments()->firstOrCreate(
            ['project_id' => $project->id, 'termin_number' => 1],
            [
                'name' => 'Termin 1 (Uang Muka)',
                'percentage' => 20,
                'amount' => round(1_980_610_920.00 * 0.20, 2),
                'target_date' => $project->start_date?->clone()->addDays(14),
                'trigger' => 'Penandatanganan kontrak & penyerahan jaminan uang muka',
                'required_items' => 'Invoice, Kwitansi, Faktur Pajak, Jaminan Uang Muka',
                'status' => PaymentStatus::Pending->value,
            ]
        );
    }
}
