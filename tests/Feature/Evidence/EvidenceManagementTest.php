<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Livewire\Evidence\Manager;
use App\Models\DocumentRequirement;
use App\Models\Evidence;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Personnel;
use App\Models\PersonnelAssignment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function makeEvidenceProjectAdmin(?Organization $organization = null): array
{
    $organization ??= Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);
    $project = Project::factory()->create(['organization_id' => $organization->id]);

    return [$user, $project];
}

it('uploads an evidence file linked to a payment, personnel, and requirement', function (): void {
    Storage::fake('local');

    [$user, $project] = makeEvidenceProjectAdmin();
    $payment = Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 1]);
    $personnel = Personnel::factory()->create(['organization_id' => $project->organization_id]);
    PersonnelAssignment::factory()->create(['project_id' => $project->id, 'personnel_id' => $personnel->id]);
    $requirement = DocumentRequirement::factory()->create();

    Livewire::actingAs($user)
        ->test(Manager::class, ['project' => $project])
        ->set('name', 'Foto Serah Terima')
        ->set('category', 'Dokumentasi')
        ->set('payment_id', $payment->id)
        ->set('personnel_id', $personnel->id)
        ->set('document_requirement_id', $requirement->id)
        ->set('file', UploadedFile::fake()->image('foto.jpg'))
        ->call('upload')
        ->assertHasNoErrors();

    $evidence = Evidence::query()->where('project_id', $project->id)->firstOrFail();
    expect($evidence->name)->toBe('Foto Serah Terima');
    expect($evidence->payment_id)->toBe($payment->id);
    expect($evidence->personnel_id)->toBe($personnel->id);
    expect($evidence->document_requirement_id)->toBe($requirement->id);
    Storage::disk('local')->assertExists($evidence->path);
});

it('deletes an evidence file and removes it from storage', function (): void {
    Storage::fake('local');

    [$user, $project] = makeEvidenceProjectAdmin();

    Livewire::actingAs($user)
        ->test(Manager::class, ['project' => $project])
        ->set('name', 'Bukti')
        ->set('file', UploadedFile::fake()->create('bukti.pdf', 100))
        ->call('upload');

    $evidence = Evidence::query()->where('project_id', $project->id)->firstOrFail();
    $path = $evidence->path;

    Livewire::actingAs($user)
        ->test(Manager::class, ['project' => $project])
        ->call('deleteEvidence', $evidence->id)
        ->assertHasNoErrors();

    Storage::disk('local')->assertMissing($path);
    expect(Evidence::query()->find($evidence->id))->toBeNull();
});

it('prevents a member from another organization from uploading evidence', function (): void {
    Storage::fake('local');

    [, $project] = makeEvidenceProjectAdmin();

    $otherOrganization = Organization::factory()->create();
    $outsider = User::factory()->create(['organization_id' => $otherOrganization->id]);
    $outsider->syncRoles([RoleName::AdminPerusahaan->value]);

    Livewire::actingAs($outsider)
        ->test(Manager::class, ['project' => $project])
        ->assertForbidden();
});

it('blocks downloading evidence for a user outside the owning organization', function (): void {
    Storage::fake('local');

    [$user, $project] = makeEvidenceProjectAdmin();

    Livewire::actingAs($user)
        ->test(Manager::class, ['project' => $project])
        ->set('name', 'Bukti')
        ->set('file', UploadedFile::fake()->create('bukti.pdf', 100))
        ->call('upload');

    $evidence = Evidence::query()->where('project_id', $project->id)->firstOrFail();

    $otherOrganization = Organization::factory()->create();
    $outsider = User::factory()->create(['organization_id' => $otherOrganization->id]);
    $outsider->syncRoles([RoleName::AdminPerusahaan->value]);

    $this->actingAs($outsider)
        ->get(route('evidences.download', $evidence))
        ->assertForbidden();
});
