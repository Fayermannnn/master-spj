<?php

declare(strict_types=1);

use App\Domain\DocumentGenerator\Contracts\PdfConverterInterface;
use App\Domain\DocumentGenerator\Services\DocumentGeneratorService;
use App\Domain\DocumentTemplate\Enums\TemplateStatus;
use App\Domain\Identity\Enums\RoleName;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Domain\Spj\Services\SpjExportService;
use App\Domain\Spj\Services\SpjPackageService;
use App\Livewire\SpjPackages\Manager;
use App\Models\Contract;
use App\Models\Document;
use App\Models\DocumentRequirement;
use App\Models\DocumentTemplate;
use App\Models\Evidence;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Project;
use App\Models\SpjPackage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * PDF converter palsu untuk test — sama seperti Phase 7
 * (tests/Feature/GeneratedDocuments/DocumentGenerationTest.php), tidak
 * menjalankan LibreOffice sungguhan di test suite.
 */
final class FakeSpjPdfConverter implements PdfConverterInterface
{
    public function convert(string $absoluteDocxPath): string
    {
        $pdfPath = (string) preg_replace('/\.docx$/', '.pdf', $absoluteDocxPath);
        file_put_contents($pdfPath, '%PDF-1.4 fake');

        return $pdfPath;
    }
}

function minimalSpjTemplateDocxBytes(): string
{
    $path = tempnam(sys_get_temp_dir(), 'docx_spj_').'.docx';

    $documentXml = '<?xml version="1.0"?>'
        .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
        .'<w:p><w:r><w:t>{{project.name}}</w:t></w:r></w:p>'
        .'</w:body></w:document>';
    $settingsXml = '<?xml version="1.0"?><w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"></w:settings>';

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"></Types>');
    $zip->addFromString('word/document.xml', $documentXml);
    $zip->addFromString('word/settings.xml', $settingsXml);
    $zip->close();

    $bytes = file_get_contents($path);
    @unlink($path);

    return $bytes;
}

/**
 * @return array{user: User, project: Project, requirement: DocumentRequirement, document: Document}
 */
function buildSpjScenario(): array
{
    Storage::fake('local');
    app()->bind(PdfConverterInterface::class, FakeSpjPdfConverter::class);

    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    $project = Project::factory()->create(['organization_id' => $organization->id]);
    Contract::factory()->create(['project_id' => $project->id]);

    $requirement = DocumentRequirement::factory()->create();
    $templatePath = "document-templates/{$requirement->id}/template.docx";
    Storage::disk('local')->put($templatePath, minimalSpjTemplateDocxBytes());

    $template = DocumentTemplate::factory()->create([
        'document_requirement_id' => $requirement->id,
        'version' => 1,
        'status' => TemplateStatus::Active->value,
        'disk' => 'local',
        'path' => $templatePath,
        'detected_variables' => ['project.name'],
    ]);

    $document = app(DocumentGeneratorService::class)->generate(
        $project,
        $requirement,
        $template,
        ['project.name' => $project->name],
        null,
        $user,
    );

    return compact('user', 'project', 'requirement', 'document');
}

it('creates a per-termin package and a project-level package', function (): void {
    ['user' => $user, 'project' => $project] = buildSpjScenario();
    $payment = Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 1]);

    Livewire::actingAs($user)
        ->test(Manager::class, ['project' => $project])
        ->set('newPackageName', 'SPJ Termin 1')
        ->set('newPackagePaymentId', $payment->id)
        ->call('createPackage')
        ->assertHasNoErrors();

    Livewire::actingAs($user)
        ->test(Manager::class, ['project' => $project])
        ->set('newPackageName', 'SPJ Akhir')
        ->call('createPackage')
        ->assertHasNoErrors();

    $termin = SpjPackage::query()->where('name', 'SPJ Termin 1')->firstOrFail();
    $final = SpjPackage::query()->where('name', 'SPJ Akhir')->firstOrFail();

    expect($termin->payment_id)->toBe($payment->id);
    expect($termin->isProjectLevel())->toBeFalse();
    expect($final->payment_id)->toBeNull();
    expect($final->isProjectLevel())->toBeTrue();
});

it('adds a document and an evidence to a package and computes checklist coverage', function (): void {
    ['user' => $user, 'project' => $project, 'requirement' => $requirement, 'document' => $document] = buildSpjScenario();

    $evidence = Evidence::factory()->create(['project_id' => $project->id, 'document_requirement_id' => $requirement->id]);

    $package = app(SpjPackageService::class)->create($project, 'SPJ Akhir', null, null, $user);

    $component = Livewire::actingAs($user)
        ->test(Manager::class, ['project' => $project])
        ->call('selectPackage', $package->id)
        ->call('addDocument', $document->id)
        ->assertHasNoErrors();

    expect($package->items()->count())->toBe(1);

    $coverage = app(SpjPackageService::class)->coverage($package->fresh());
    expect($coverage['covered'])->toBe(1);
    expect($coverage['total'])->toBe(1);
    expect($coverage['percentage'])->toBe(100);

    $component->call('addEvidence', $evidence->id)->assertHasNoErrors();
    expect($package->items()->count())->toBe(2);
});

it('rejects adding the same document twice to a package', function (): void {
    ['project' => $project, 'document' => $document, 'user' => $user] = buildSpjScenario();
    $service = app(SpjPackageService::class);
    $package = $service->create($project, 'SPJ Akhir', null, null, $user);

    $service->addDocument($package, $document);
    $service->addDocument($package, $document);
})->throws(DomainActionException::class);

it('blocks manifest changes once a package is finalized', function (): void {
    ['project' => $project, 'document' => $document, 'user' => $user] = buildSpjScenario();
    $service = app(SpjPackageService::class);
    $package = $service->create($project, 'SPJ Akhir', null, null, $user);
    $service->addDocument($package, $document);
    $service->finalize($package);

    $otherRequirement = DocumentRequirement::factory()->create();
    $evidence = Evidence::factory()->create(['project_id' => $project->id, 'document_requirement_id' => $otherRequirement->id]);

    $service->addEvidence($package->fresh(), $evidence);
})->throws(DomainActionException::class);

it('rejects finalizing an empty package', function (): void {
    ['project' => $project, 'user' => $user] = buildSpjScenario();
    $service = app(SpjPackageService::class);
    $package = $service->create($project, 'SPJ Kosong', null, null, $user);

    $service->finalize($package);
})->throws(DomainActionException::class);

it('removes an item from a draft package', function (): void {
    ['project' => $project, 'document' => $document, 'user' => $user] = buildSpjScenario();
    $service = app(SpjPackageService::class);
    $package = $service->create($project, 'SPJ Akhir', null, null, $user);
    $item = $service->addDocument($package, $document);

    $service->removeItem($item);

    expect($package->items()->count())->toBe(0);
});

it('exports a package as a ZIP containing the document PDF and a manifest', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'document' => $document, 'user' => $user] = buildSpjScenario();
    $service = app(SpjPackageService::class);
    $package = $service->create($project, 'SPJ Akhir', null, null, $user);
    $service->addDocument($package, $document);

    $zipPath = app(SpjExportService::class)->export($package->fresh(['items.document', 'items.evidence', 'project', 'payment']));

    expect(is_file($zipPath))->toBeTrue();

    $zip = new ZipArchive;
    $zip->open($zipPath);
    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }
    $manifestContent = $zip->getFromName('manifest.txt');
    $zip->close();
    @unlink($zipPath);

    expect($names)->toContain('manifest.txt');
    expect(count($names))->toBe(2);
    expect($manifestContent)->toContain($project->name);
    expect($manifestContent)->toContain($requirement->name);
});

it('prevents a member from another organization from managing packages', function (): void {
    ['project' => $project] = buildSpjScenario();

    $otherOrganization = Organization::factory()->create();
    $outsider = User::factory()->create(['organization_id' => $otherOrganization->id]);
    $outsider->syncRoles([RoleName::AdminPerusahaan->value]);

    Livewire::actingAs($outsider)
        ->test(Manager::class, ['project' => $project])
        ->assertForbidden();
});
