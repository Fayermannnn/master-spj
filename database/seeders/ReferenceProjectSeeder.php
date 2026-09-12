<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Client\Enums\ContactType;
use App\Domain\ProjectManagement\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectType;
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

        Contract::query()->firstOrCreate(
            ['project_id' => $project->id],
            [
                'contract_number' => '027/KTR-KONSULTANSI/RSPNDD/2026',
                'contract_date' => $startDate,
                'spmk_number' => '028/SPMK/RSPNDD/2026',
                'spmk_date' => $startDate,
                'contract_value' => 1_980_610_920.00,
                'tax_amount' => round(1_980_610_920.00 * 11 / 111, 2),
                'net_value' => round(1_980_610_920.00 * 100 / 111, 2),
                'notes' => 'Reference/sample project — lihat PROJECT_BLUEPRINT.md §3.',
            ]
        );
    }
}
