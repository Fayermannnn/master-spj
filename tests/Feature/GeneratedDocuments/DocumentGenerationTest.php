<?php

declare(strict_types=1);

use App\Domain\DocumentGenerator\Contracts\PdfConverterInterface;
use App\Domain\DocumentGenerator\Services\DocumentGeneratorService;
use App\Domain\DocumentRequirement\Enums\ChecklistStatus;
use App\Domain\DocumentRequirement\Services\ChecklistService;
use App\Domain\DocumentTemplate\Enums\TemplateStatus;
use App\Domain\Identity\Enums\RoleName;
use App\Domain\Organization\Services\OrganizationService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Livewire\GeneratedDocuments\Manager;
use App\Models\Contract;
use App\Models\Deliverable;
use App\Models\Document;
use App\Models\DocumentRequirement;
use App\Models\DocumentTemplate;
use App\Models\NumberingSetting;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Personnel;
use App\Models\PersonnelAssignment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * PDF converter palsu — konversi PDF sungguhan (LibreOffice) TIDAK
 * dijalankan di test suite (proses eksternal, lambat, tergantung
 * lingkungan) — hanya `LibreOfficePdfConverter` yang menjalankannya
 * secara nyata di production. Lihat PROJECT_DECISIONS.md D-018.
 */
final class FakePdfConverter implements PdfConverterInterface
{
    public function convert(string $absoluteDocxPath): string
    {
        $pdfPath = (string) preg_replace('/\.docx$/', '.pdf', $absoluteDocxPath);
        file_put_contents($pdfPath, '%PDF-1.4 fake');

        return $pdfPath;
    }
}

/**
 * Docx berisi placeholder skalar (project.name, client.address,
 * deliverable.name) dan satu baris tabel personel (personnel.name/
 * position/npwp) — dibangun manual lewat ZipArchive, bukan file Word
 * sungguhan, tapi struktur OOXML minimal yang valid dibaca
 * `phpoffice/phpword` TemplateProcessor (perlu word/settings.xml, bukan
 * hanya word/document.xml, seperti punya DocxPlaceholderScanner).
 */
function generatorTemplateDocxBytes(): string
{
    $path = tempnam(sys_get_temp_dir(), 'docx_gen_').'.docx';

    $documentXml = '<?xml version="1.0"?>'
        .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
        .'<w:p><w:r><w:t>Project: {{project.name}}</w:t></w:r></w:p>'
        .'<w:p><w:r><w:t>Client: {{client.address}}</w:t></w:r></w:p>'
        .'<w:p><w:r><w:t>Deliverable: {{deliverable.name}}</w:t></w:r></w:p>'
        .'<w:tbl><w:tblPr/><w:tblGrid><w:gridCol w:w="2000"/><w:gridCol w:w="2000"/><w:gridCol w:w="2000"/></w:tblGrid>'
        .'<w:tr>'
        .'<w:tc><w:tcPr/><w:p><w:r><w:t>{{personnel.name}}</w:t></w:r></w:p></w:tc>'
        .'<w:tc><w:tcPr/><w:p><w:r><w:t>{{personnel.position}}</w:t></w:r></w:p></w:tc>'
        .'<w:tc><w:tcPr/><w:p><w:r><w:t>{{personnel.npwp}}</w:t></w:r></w:p></w:tc>'
        .'</w:tr>'
        .'</w:tbl>'
        .'</w:body></w:document>';

    $settingsXml = '<?xml version="1.0"?><w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"></w:settings>';

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString(
        '[Content_Types].xml',
        '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"></Types>'
    );
    $zip->addFromString('word/document.xml', $documentXml);
    $zip->addFromString('word/settings.xml', $settingsXml);
    $zip->close();

    $bytes = file_get_contents($path);
    @unlink($path);

    return $bytes;
}

/**
 * Docx terpisah berisi HANYA placeholder `{{organization.logo}}` —
 * `generatorTemplateDocxBytes()` sengaja TIDAK menyertakannya supaya
 * test lain yang tidak mendaftarkan `organization.logo` di
 * `detected_variables` tidak ikut kebocoran teks placeholder mentah ke
 * hasil generate mereka.
 */
function logoPlaceholderDocxBytes(): string
{
    $path = tempnam(sys_get_temp_dir(), 'docx_logo_').'.docx';

    $documentXml = '<?xml version="1.0"?>'
        .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
        .'<w:p><w:r><w:t>{{organization.logo}}</w:t></w:r></w:p>'
        .'<w:p><w:r><w:t>Project: {{project.name}}</w:t></w:r></w:p>'
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
 * @return array{project: Project, requirement: DocumentRequirement, template: DocumentTemplate, user: User}
 */
function buildGenerationScenario(): array
{
    Storage::fake('local');

    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    $project = Project::factory()->create(['organization_id' => $organization->id]);
    Contract::factory()->create(['project_id' => $project->id]);

    $personnel = Personnel::factory()->create(['organization_id' => $organization->id]);
    PersonnelAssignment::factory()->create([
        'project_id' => $project->id,
        'personnel_id' => $personnel->id,
        'role_on_project' => 'Team Leader',
    ]);

    $requirement = DocumentRequirement::factory()->create();

    $templatePath = "document-templates/{$requirement->id}/template.docx";
    Storage::disk('local')->put($templatePath, generatorTemplateDocxBytes());

    $template = DocumentTemplate::factory()->create([
        'document_requirement_id' => $requirement->id,
        'version' => 1,
        'status' => TemplateStatus::Active->value,
        'disk' => 'local',
        'path' => $templatePath,
        'detected_variables' => [
            'project.name', 'client.address', 'deliverable.name',
            'personnel.name', 'personnel.position', 'personnel.npwp',
        ],
    ]);

    return compact('project', 'requirement', 'template', 'user');
}

function extractDocxText(string $disk, string $path): string
{
    $absolute = Storage::disk($disk)->path($path);
    $zip = new ZipArchive;
    $zip->open($absolute);
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    return strip_tags((string) $xml);
}

beforeEach(function (): void {
    app()->bind(PdfConverterInterface::class, FakePdfConverter::class);
});

it('fills scalar and table placeholders, saves docx+pdf, and fulfills the checklist item', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'template' => $template] = buildGenerationScenario();

    app(ChecklistService::class)->sync($project);
    $checklistItem = $project->checklistItems()->where('document_requirement_id', $requirement->id)->firstOrFail();
    expect($checklistItem->status)->toBe(ChecklistStatus::Missing);

    $document = app(DocumentGeneratorService::class)->generate(
        $project,
        $requirement,
        $template,
        ['project.name' => $project->name, 'client.address' => 'Jl. Contoh No. 1', 'deliverable.name' => 'Laporan Akhir'],
        null,
        null,
    );

    expect($document->version)->toBe(1);
    expect($document->hasPdf())->toBeTrue();
    Storage::disk('local')->assertExists($document->path);
    Storage::disk('local')->assertExists((string) $document->pdf_path);

    $text = extractDocxText($document->disk, $document->path);
    expect($text)
        ->toContain('Project: '.$project->name)
        ->toContain('Client: Jl. Contoh No. 1')
        ->toContain('Deliverable: Laporan Akhir')
        ->toContain('Team Leader')
        ->not->toContain('{{')
        ->not->toContain('}}');

    expect($checklistItem->fresh()->status)->toBe(ChecklistStatus::Fulfilled);
});

it('increments the document version on subsequent generations for the same requirement', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'template' => $template] = buildGenerationScenario();
    $service = app(DocumentGeneratorService::class);
    $values = ['project.name' => 'A', 'client.address' => 'B', 'deliverable.name' => 'C'];

    $first = $service->generate($project, $requirement, $template, $values, null, null);
    $second = $service->generate($project, $requirement, $template, $values, null, null);

    expect($first->version)->toBe(1);
    expect($second->version)->toBe(2);
});

it('rejects generating from a non-active template version', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'template' => $template] = buildGenerationScenario();
    $template->update(['status' => TemplateStatus::Draft->value]);

    app(DocumentGeneratorService::class)->generate($project, $requirement, $template, [], null, null);
})->throws(DomainActionException::class);

it('deletes the generated docx and pdf files together with the record', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'template' => $template] = buildGenerationScenario();
    $service = app(DocumentGeneratorService::class);

    $document = $service->generate($project, $requirement, $template, ['project.name' => 'A', 'client.address' => 'B', 'deliverable.name' => 'C'], null, null);
    $docxPath = $document->path;
    $pdfPath = (string) $document->pdf_path;

    $service->delete($document);

    Storage::disk('local')->assertMissing($docxPath);
    Storage::disk('local')->assertMissing($pdfPath);
    expect(Document::withTrashed()->find($document->id)?->trashed())->toBeTrue();
});

it('returns a 404 instead of crashing when the docx file is missing from disk but the record remains', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'template' => $template, 'user' => $user] = buildGenerationScenario();
    $document = app(DocumentGeneratorService::class)->generate($project, $requirement, $template, ['project.name' => 'A', 'client.address' => 'B', 'deliverable.name' => 'C'], null, null);

    Storage::disk($document->disk)->delete($document->path);

    $this->actingAs($user)
        ->get(route('generated-documents.download', ['document' => $document, 'type' => 'docx']))
        ->assertNotFound();
});

it('returns a 404 instead of crashing when the pdf file is missing from disk but the record remains', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'template' => $template, 'user' => $user] = buildGenerationScenario();
    $document = app(DocumentGeneratorService::class)->generate($project, $requirement, $template, ['project.name' => 'A', 'client.address' => 'B', 'deliverable.name' => 'C'], null, null);

    Storage::disk((string) $document->pdf_disk)->delete((string) $document->pdf_path);

    $this->actingAs($user)
        ->get(route('generated-documents.download', ['document' => $document, 'type' => 'pdf']))
        ->assertNotFound();
});

it('lets a project member generate a document through the Livewire manager', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'template' => $template, 'user' => $user] = buildGenerationScenario();

    $component = Livewire::actingAs($user)
        ->test(Manager::class, ['project' => $project])
        ->call('openGenerateForm', $requirement->id);

    $keys = $component->instance()->tablelessDetectedKeys($template);
    $deliverableIndex = array_search('deliverable.name', $keys, true);
    expect($deliverableIndex)->not->toBeFalse();

    $component
        ->set("variableInputs.{$deliverableIndex}", 'Laporan Pendahuluan')
        ->call('generate')
        ->assertHasNoErrors();

    $document = Document::query()->where('project_id', $project->id)->where('document_requirement_id', $requirement->id)->firstOrFail();
    expect($document->data_snapshot['deliverable.name'])->toBe('Laporan Pendahuluan');
});

it('prevents a member from another organization from viewing the documents tab', function (): void {
    ['project' => $project] = buildGenerationScenario();

    $otherOrganization = Organization::factory()->create();
    $outsider = User::factory()->create(['organization_id' => $otherOrganization->id]);
    $outsider->syncRoles([RoleName::AdminPerusahaan->value]);

    Livewire::actingAs($outsider)
        ->test(Manager::class, ['project' => $project])
        ->assertForbidden();
});

it('resolves the payment context into payment.* placeholders when selected', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'template' => $template] = buildGenerationScenario();
    $template->update(['detected_variables' => array_merge($template->detected_variables, ['payment.amount'])]);

    $payment = Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 1, 'amount' => 150_000_000]);

    $document = app(DocumentGeneratorService::class)->generate(
        $project,
        $requirement,
        $template->fresh(),
        ['project.name' => 'A', 'client.address' => 'B', 'deliverable.name' => 'C', 'payment.amount' => 'Rp 150.000.000'],
        $payment,
        null,
    );

    expect($document->payment_id)->toBe($payment->id);
    expect($document->data_snapshot['payment.amount'])->toBe('Rp 150.000.000');
});

it('auto-fills payment.name/percentage/trigger/amount_terbilang when a payment is selected', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'template' => $template, 'user' => $user] = buildGenerationScenario();
    $template->update(['detected_variables' => array_merge($template->detected_variables, [
        'payment.name', 'payment.percentage', 'payment.trigger', 'payment.amount_terbilang',
    ])]);
    $payment = Payment::factory()->create([
        'project_id' => $project->id,
        'termin_number' => 1,
        'name' => 'Termin 1',
        'percentage' => 20,
        'trigger' => 'Penandatanganan kontrak',
        'amount' => 100_000_000,
    ]);

    $component = Livewire::actingAs($user)
        ->test(Manager::class, ['project' => $project])
        ->call('openGenerateForm', $requirement->id)
        ->set('selectedPaymentId', $payment->id);

    $keys = $component->instance()->tablelessDetectedKeys($template->fresh());
    $inputs = $component->get('variableInputs');

    expect($inputs[array_search('payment.name', $keys, true)])->toBe('Termin 1');
    expect($inputs[array_search('payment.percentage', $keys, true)])->toBe('20%');
    expect($inputs[array_search('payment.trigger', $keys, true)])->toBe('Penandatanganan kontrak');
    expect($inputs[array_search('payment.amount_terbilang', $keys, true)])->toBe('Seratus Juta Rupiah');
});

it('links a generated document to a real Deliverable when one is selected', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'template' => $template] = buildGenerationScenario();

    $deliverable = Deliverable::factory()->create(['project_id' => $project->id, 'name' => 'Laporan Pendahuluan']);

    $document = app(DocumentGeneratorService::class)->generate(
        $project,
        $requirement,
        $template,
        ['project.name' => 'A', 'client.address' => 'B', 'deliverable.name' => $deliverable->name],
        null,
        null,
        $deliverable,
    );

    expect($document->deliverable_id)->toBe($deliverable->id);
    expect($document->data_snapshot['deliverable.name'])->toBe('Laporan Pendahuluan');
});

it('rejects generating with a deliverable from a different project', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'template' => $template] = buildGenerationScenario();

    $otherProject = Project::factory()->create();
    $deliverable = Deliverable::factory()->create(['project_id' => $otherProject->id]);

    app(DocumentGeneratorService::class)->generate(
        $project,
        $requirement,
        $template,
        ['project.name' => 'A', 'client.address' => 'B', 'deliverable.name' => 'C'],
        null,
        null,
        $deliverable,
    );
})->throws(DomainActionException::class);

it('auto-fills the deliverable.name variable when a Deliverable is selected through the Livewire manager', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'template' => $template, 'user' => $user] = buildGenerationScenario();
    $deliverable = Deliverable::factory()->create(['project_id' => $project->id, 'name' => 'Laporan Akhir']);

    $component = Livewire::actingAs($user)
        ->test(Manager::class, ['project' => $project])
        ->call('openGenerateForm', $requirement->id)
        ->set('selectedDeliverableId', $deliverable->id);

    $keys = $component->instance()->tablelessDetectedKeys($template);
    $deliverableIndex = array_search('deliverable.name', $keys, true);

    expect($component->get('variableInputs')[$deliverableIndex])->toBe('Laporan Akhir');

    $component
        ->set("variableInputs.{$deliverableIndex}", $deliverable->name)
        ->call('generate')
        ->assertHasNoErrors();

    $document = Document::query()->where('project_id', $project->id)->where('document_requirement_id', $requirement->id)->firstOrFail();
    expect($document->deliverable_id)->toBe($deliverable->id);
});

it('injects an auto-generated document number when the template detects the placeholder', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'template' => $template] = buildGenerationScenario();
    $template->update(['detected_variables' => array_merge($template->detected_variables, ['document.number'])]);
    NumberingSetting::factory()->create([
        'organization_id' => $project->organization_id,
        'format_template' => '{seq}/SPJ/{org}/{year}',
        'reset_period' => 'never',
        'next_sequence' => 1,
    ]);

    $document = app(DocumentGeneratorService::class)->generate(
        $project,
        $requirement,
        $template->fresh(),
        ['project.name' => 'A', 'client.address' => 'B', 'deliverable.name' => 'C'],
        null,
        null,
    );

    expect($document->number)->not->toBeNull();
    expect($document->data_snapshot['document.number'])->toBe($document->number);
});

it('leaves the document number null when the organization has no numbering setting', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'template' => $template] = buildGenerationScenario();
    $template->update(['detected_variables' => array_merge($template->detected_variables, ['document.number'])]);

    $document = app(DocumentGeneratorService::class)->generate(
        $project,
        $requirement,
        $template->fresh(),
        ['project.name' => 'A', 'client.address' => 'B', 'deliverable.name' => 'C'],
        null,
        null,
    );

    expect($document->number)->toBeNull();
});

it('injects the organization logo as an image when the template detects the placeholder', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'template' => $template] = buildGenerationScenario();
    Storage::disk($template->disk)->put($template->path, logoPlaceholderDocxBytes());
    $template->update(['detected_variables' => ['organization.logo', 'project.name']]);
    app(OrganizationService::class)->updateLogo(
        $project->organization,
        UploadedFile::fake()->image('logo.png'),
    );

    $document = app(DocumentGeneratorService::class)->generate(
        $project,
        $requirement,
        $template->fresh(),
        ['project.name' => 'A', 'client.address' => 'B', 'deliverable.name' => 'C'],
        null,
        null,
    );

    $text = extractDocxText($document->disk, $document->path);
    expect($text)->not->toContain('organization.logo');

    $zip = new ZipArchive;
    $zip->open(Storage::disk($document->disk)->path($document->path));
    $mediaFiles = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $mediaFiles[] = $zip->getNameIndex($i);
    }
    $zip->close();

    expect(collect($mediaFiles)->contains(fn (string $name): bool => str_starts_with($name, 'word/media/')))->toBeTrue();
});

it('leaves the logo placeholder blank when the organization has no logo uploaded', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'template' => $template] = buildGenerationScenario();
    Storage::disk($template->disk)->put($template->path, logoPlaceholderDocxBytes());
    $template->update(['detected_variables' => ['organization.logo', 'project.name']]);

    $document = app(DocumentGeneratorService::class)->generate(
        $project,
        $requirement,
        $template->fresh(),
        ['project.name' => 'A', 'client.address' => 'B', 'deliverable.name' => 'C'],
        null,
        null,
    );

    $text = extractDocxText($document->disk, $document->path);
    expect($text)->not->toContain('organization.logo');
});

it('does not render document.number as an editable input in the generate form', function (): void {
    ['project' => $project, 'requirement' => $requirement, 'template' => $template, 'user' => $user] = buildGenerationScenario();
    $template->update(['detected_variables' => array_merge($template->detected_variables, ['document.number'])]);

    $component = Livewire::actingAs($user)
        ->test(Manager::class, ['project' => $project])
        ->call('openGenerateForm', $requirement->id);

    $keys = $component->instance()->tablelessDetectedKeys($template->fresh());

    expect($keys)->not->toContain('document.number');
});
