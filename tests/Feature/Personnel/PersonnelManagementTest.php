<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Domain\Personnel\Services\PersonnelService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Livewire\Personnel\Documents;
use App\Livewire\Personnel\Form;
use App\Models\Organization;
use App\Models\Personnel;
use App\Models\PersonnelAssignment;
use App\Models\PersonnelCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function makePersonnelAdminForOrg(Organization $organization): User
{
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    return $user;
}

it('creates a personnel record scoped to the actor organization', function (): void {
    $organization = Organization::factory()->create();
    $admin = makePersonnelAdminForOrg($organization);
    $category = PersonnelCategory::factory()->create();

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('personnel_category_id', $category->id)
        ->set('name', 'Budi Santoso')
        ->call('save')
        ->assertHasNoErrors();

    $personnel = Personnel::query()->where('name', 'Budi Santoso')->firstOrFail();
    expect($personnel->organization_id)->toBe($organization->id);
});

it('forces organization_id to the actor own organization even if tampered client-side', function (): void {
    $ownOrganization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $admin = makePersonnelAdminForOrg($ownOrganization);
    $category = PersonnelCategory::factory()->create();

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('organization_id', $otherOrganization->id)
        ->set('personnel_category_id', $category->id)
        ->set('name', 'Tamper Test')
        ->call('save');

    $personnel = Personnel::query()->where('name', 'Tamper Test')->firstOrFail();
    expect($personnel->organization_id)->toBe($ownOrganization->id);
});

it('blocks deleting personnel that still has project assignments', function (): void {
    $personnel = Personnel::factory()->create();
    PersonnelAssignment::factory()->create(['personnel_id' => $personnel->id]);

    expect(fn () => app(PersonnelService::class)->delete($personnel))
        ->toThrow(DomainActionException::class);
});

it('uploads, downloads, and deletes a personnel document within policy bounds', function (): void {
    Storage::fake('local');

    $organization = Organization::factory()->create();
    $admin = makePersonnelAdminForOrg($organization);
    $personnel = Personnel::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($admin)
        ->test(Documents::class, ['personnel' => $personnel])
        ->set('document_type', 'ktp')
        ->set('file', UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'))
        ->call('upload')
        ->assertHasNoErrors();

    $document = $personnel->documents()->firstOrFail();
    Storage::disk('local')->assertExists($document->path);

    $response = $this->actingAs($admin)->get(route('personnel-documents.download', $document));
    $response->assertOk();

    Livewire::actingAs($admin)
        ->test(Documents::class, ['personnel' => $personnel])
        ->call('deleteDocument', $document->id);

    expect($personnel->documents()->count())->toBe(0);
    Storage::disk('local')->assertMissing($document->path);
});

it('returns a 404 instead of crashing when the personnel document file is missing from disk but the record remains', function (): void {
    Storage::fake('local');

    $organization = Organization::factory()->create();
    $admin = makePersonnelAdminForOrg($organization);
    $personnel = Personnel::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($admin)
        ->test(Documents::class, ['personnel' => $personnel])
        ->set('document_type', 'ktp')
        ->set('file', UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'))
        ->call('upload');

    $document = $personnel->documents()->firstOrFail();
    Storage::disk($document->disk)->delete($document->path);

    $response = $this->actingAs($admin)->get(route('personnel-documents.download', $document));
    $response->assertNotFound();
});

it('prevents a user from another organization downloading a personnel document', function (): void {
    Storage::fake('local');

    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $otherAdmin = makePersonnelAdminForOrg($otherOrganization);
    $personnel = Personnel::factory()->create(['organization_id' => $organization->id]);
    $document = $personnel->documents()->create([
        'document_type' => 'ktp',
        'disk' => 'local',
        'path' => 'personnel/fake/ktp.pdf',
        'original_filename' => 'ktp.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
    ]);

    $response = $this->actingAs($otherAdmin)->get(route('personnel-documents.download', $document));
    $response->assertForbidden();
});
