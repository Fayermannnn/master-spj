<?php

declare(strict_types=1);

use App\Domain\DocumentTemplate\Enums\TemplateStatus;
use App\Domain\Identity\Enums\RoleName;
use App\Livewire\DocumentTemplates\Manager;
use App\Models\DocumentRequirement;
use App\Models\DocumentTemplate;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Byte docx minimal berisi satu placeholder, dipakai untuk menguji alur
 * upload sungguhan (bukan file fake tanpa isi) tanpa Word/LibreOffice.
 */
function minimalDocxBytes(string $placeholder = 'project.name'): string
{
    $path = tempnam(sys_get_temp_dir(), 'docx_upload_').'.docx';

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString(
        '[Content_Types].xml',
        '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"></Types>'
    );
    $zip->addFromString(
        'word/document.xml',
        '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>{{'.$placeholder.'}}</w:t></w:r></w:p></w:body></w:document>'
    );
    $zip->close();

    $bytes = file_get_contents($path);
    @unlink($path);

    return $bytes;
}

function makeTemplateSuperAdmin(): User
{
    $user = User::factory()->create();
    $user->syncRoles([RoleName::SuperAdmin->value]);

    return $user;
}

it('uploads a template as version 1 and detects its placeholder', function (): void {
    Storage::fake('local');

    $admin = makeTemplateSuperAdmin();
    $requirement = DocumentRequirement::factory()->create();
    $file = UploadedFile::fake()->createWithContent('ba.docx', minimalDocxBytes('project.name'));

    Livewire::actingAs($admin)
        ->test(Manager::class, ['documentRequirement' => $requirement])
        ->set('name', 'Berita Acara')
        ->set('file', $file)
        ->call('upload')
        ->assertHasNoErrors();

    $template = DocumentTemplate::query()->where('document_requirement_id', $requirement->id)->firstOrFail();
    expect($template->version)->toBe(1);
    expect($template->detected_variables)->toBe(['project.name']);
    expect($template->status)->toBe(TemplateStatus::Draft);
});

it('increments the version number on a second upload', function (): void {
    Storage::fake('local');

    $admin = makeTemplateSuperAdmin();
    $requirement = DocumentRequirement::factory()->create();

    Livewire::actingAs($admin)
        ->test(Manager::class, ['documentRequirement' => $requirement])
        ->set('name', 'Berita Acara v1')
        ->set('file', UploadedFile::fake()->createWithContent('ba1.docx', minimalDocxBytes()))
        ->call('upload');

    Livewire::actingAs($admin)
        ->test(Manager::class, ['documentRequirement' => $requirement])
        ->set('name', 'Berita Acara v2')
        ->set('file', UploadedFile::fake()->createWithContent('ba2.docx', minimalDocxBytes()))
        ->call('upload');

    $versions = DocumentTemplate::query()->where('document_requirement_id', $requirement->id)->pluck('version')->sort()->values();
    expect($versions->all())->toBe([1, 2]);
});

it('archives the previously active version when a new one is activated', function (): void {
    Storage::fake('local');

    $admin = makeTemplateSuperAdmin();
    $requirement = DocumentRequirement::factory()->create();
    $v1 = DocumentTemplate::factory()->create([
        'document_requirement_id' => $requirement->id,
        'version' => 1,
        'status' => TemplateStatus::Active->value,
    ]);
    $v2 = DocumentTemplate::factory()->create([
        'document_requirement_id' => $requirement->id,
        'version' => 2,
        'status' => TemplateStatus::Draft->value,
    ]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['documentRequirement' => $requirement])
        ->call('activate', $v2->id);

    expect($v1->fresh()->status)->toBe(TemplateStatus::Archived);
    expect($v2->fresh()->status)->toBe(TemplateStatus::Active);
});

it('blocks deleting the active template version', function (): void {
    Storage::fake('local');

    $admin = makeTemplateSuperAdmin();
    $requirement = DocumentRequirement::factory()->create();
    $active = DocumentTemplate::factory()->create([
        'document_requirement_id' => $requirement->id,
        'status' => TemplateStatus::Active->value,
    ]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['documentRequirement' => $requirement])
        ->call('delete', $active->id);

    expect(DocumentTemplate::query()->find($active->id))->not->toBeNull();
});

it('prevents a non super-admin from uploading a template', function (): void {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->syncRoles([RoleName::AdminPerusahaan->value]);
    $requirement = DocumentRequirement::factory()->create();

    Livewire::actingAs($admin)
        ->test(Manager::class, ['documentRequirement' => $requirement])
        ->set('name', 'Percobaan')
        ->set('file', UploadedFile::fake()->createWithContent('x.docx', minimalDocxBytes()))
        ->call('upload')
        ->assertForbidden();
});
