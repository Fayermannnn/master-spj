<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Livewire\ProjectPersonnel\Manager;
use App\Models\Organization;
use App\Models\Personnel;
use App\Models\PersonnelAssignment;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

function makeAssignmentAdmin(Organization $organization): User
{
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    return $user;
}

it('assigns a personnel to a project and computes the subtotal', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeAssignmentAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $personnel = Personnel::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->set('personnel_id', $personnel->id)
        ->set('quantity', '2')
        ->set('unit', 'OB')
        ->set('unit_price', '10000000')
        ->call('save')
        ->assertHasNoErrors();

    $assignment = PersonnelAssignment::query()->where('project_id', $project->id)->firstOrFail();
    expect((float) $assignment->subtotal)->toBe(20_000_000.0);
});

it('rejects assigning personnel from a different organization', function (): void {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $admin = makeAssignmentAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $otherPersonnel = Personnel::factory()->create(['organization_id' => $otherOrganization->id]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->set('personnel_id', $otherPersonnel->id)
        ->set('quantity', '1')
        ->set('unit', 'OB')
        ->set('unit_price', '10000000')
        ->call('save')
        ->assertHasErrors(['personnel_id']);
});

it('prevents assigning the same personnel twice to one project', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeAssignmentAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $personnel = Personnel::factory()->create(['organization_id' => $organization->id]);
    PersonnelAssignment::factory()->create(['project_id' => $project->id, 'personnel_id' => $personnel->id]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->set('personnel_id', $personnel->id)
        ->set('quantity', '1')
        ->set('unit', 'OB')
        ->set('unit_price', '10000000')
        ->call('save')
        ->assertHasErrors(['personnel_id']);
});

it('recomputes the subtotal when an assignment is updated', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeAssignmentAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $personnel = Personnel::factory()->create(['organization_id' => $organization->id]);
    $assignment = PersonnelAssignment::factory()->create([
        'project_id' => $project->id,
        'personnel_id' => $personnel->id,
        'quantity' => 1,
        'unit_price' => 10_000_000,
        'subtotal' => 10_000_000,
    ]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->call('edit', $assignment->id)
        ->set('quantity', '3')
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $assignment->fresh()->subtotal)->toBe(30_000_000.0);
});

it('prevents a member from another organization from managing assignments on someone else\'s project', function (): void {
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);

    $otherOrganization = Organization::factory()->create();
    $outsider = makeAssignmentAdmin($otherOrganization);

    Livewire::actingAs($outsider)
        ->test(Manager::class, ['project' => $project])
        ->assertForbidden();
});
