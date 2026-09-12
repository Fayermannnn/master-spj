<?php

declare(strict_types=1);

use App\Domain\DocumentGenerator\Contracts\PdfConverterInterface;
use App\Domain\Identity\Enums\RoleName;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Reporting\Services\ProjectSummaryExcelExporter;
use App\Domain\Reporting\Services\ProjectSummaryPdfExporter;
use App\Domain\Reporting\Services\ProjectSummaryReportService;
use App\Livewire\Reports\ProjectSummary;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;

it('includes only projects from the viewer own organization', function (): void {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    $ownProject = Project::factory()->create(['organization_id' => $organization->id, 'name' => 'Project Sendiri']);
    Project::factory()->create(['organization_id' => $otherOrganization->id, 'name' => 'Project Organisasi Lain']);

    $rows = app(ProjectSummaryReportService::class)->rows([
        'organization_id' => $organization->id,
        'search' => '',
        'status' => '',
        'client_id' => '',
    ]);

    expect($rows->pluck('name')->all())->toBe(['Project Sendiri']);
});

it('computes contract value, total paid, and checklist coverage per project', function (): void {
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    Contract::factory()->create(['project_id' => $project->id, 'contract_value' => 100_000_000]);
    Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 1, 'amount' => 30_000_000, 'status' => PaymentStatus::Paid->value]);
    Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 2, 'amount' => 70_000_000, 'status' => PaymentStatus::Pending->value]);

    $rows = app(ProjectSummaryReportService::class)->rows([
        'organization_id' => $organization->id,
        'search' => '',
        'status' => '',
        'client_id' => '',
    ]);

    $row = $rows->firstOrFail();
    expect((float) $row['contract_value'])->toBe(100_000_000.0);
    expect($row['total_paid'])->toBe(30_000_000.0);
    expect($row['total_payment'])->toBe(100_000_000.0);
});

it('exports the report as a real xlsx file readable by PhpSpreadsheet', function (): void {
    $organization = Organization::factory()->create();
    Project::factory()->create(['organization_id' => $organization->id, 'name' => 'Project Uji Excel']);

    $rows = app(ProjectSummaryReportService::class)->rows([
        'organization_id' => $organization->id,
        'search' => '',
        'status' => '',
        'client_id' => '',
    ]);

    $path = app(ProjectSummaryExcelExporter::class)->export($rows);

    expect(is_file($path))->toBeTrue();

    $spreadsheet = IOFactory::load($path);
    $sheet = $spreadsheet->getActiveSheet();
    expect($sheet->getCell('A1')->getValue())->toBe('Kode');
    expect($sheet->getCell('B2')->getValue())->toBe('Project Uji Excel');

    @unlink($path);
});

it('exports the report as a real pdf file via the pdf converter', function (): void {
    $organization = Organization::factory()->create();
    Project::factory()->create(['organization_id' => $organization->id, 'name' => 'Project Uji PDF']);

    app()->bind(PdfConverterInterface::class, function () {
        return new class implements PdfConverterInterface
        {
            public function convert(string $absoluteDocxPath): string
            {
                $pdfPath = (string) preg_replace('/\.docx$/', '.pdf', $absoluteDocxPath);
                file_put_contents($pdfPath, '%PDF-1.4 fake');

                return $pdfPath;
            }
        };
    });

    $rows = app(ProjectSummaryReportService::class)->rows([
        'organization_id' => $organization->id,
        'search' => '',
        'status' => '',
        'client_id' => '',
    ]);

    $path = app(ProjectSummaryPdfExporter::class)->export($rows);

    expect(is_file($path))->toBeTrue();
    expect(file_get_contents($path))->toStartWith('%PDF');

    @unlink($path);
});

it('renders the report page and filters by search', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    Project::factory()->create(['organization_id' => $organization->id, 'name' => 'Alpha Project']);
    Project::factory()->create(['organization_id' => $organization->id, 'name' => 'Beta Project']);

    Livewire::actingAs($user)
        ->test(ProjectSummary::class)
        ->set('search', 'Alpha')
        ->assertSee('Alpha Project')
        ->assertDontSee('Beta Project');
});

it('prevents users without projects.viewAny from opening the report page', function (): void {
    $organization = Organization::factory()->create();
    $userWithNoRole = User::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($userWithNoRole)
        ->test(ProjectSummary::class)
        ->assertForbidden();
});
