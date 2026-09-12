<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Livewire\ProjectPersonnel\Manager;
use App\Models\Organization;
use App\Models\Personnel;
use App\Models\PersonnelAssignment;
use App\Models\Project;
use App\Models\TravelAssignment;
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

it('adds a travel assignment for a personnel on the project', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeAssignmentAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $personnel = Personnel::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->call('openTravelForm')
        ->set('travel_personnel_id', $personnel->id)
        ->set('travel_destination', 'Samarinda')
        ->set('travel_purpose', 'Koordinasi dengan PPK')
        ->set('travel_departure_date', '2026-03-01')
        ->set('travel_return_date', '2026-03-05')
        ->call('saveTravel')
        ->assertHasNoErrors();

    $travel = $project->travelAssignments()->firstOrFail();
    expect($travel->personnel_id)->toBe($personnel->id);
    expect($travel->destination)->toBe('Samarinda');
});

it('rejects a travel assignment where the return date is before the departure date', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeAssignmentAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $personnel = Personnel::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->call('openTravelForm')
        ->set('travel_personnel_id', $personnel->id)
        ->set('travel_destination', 'Samarinda')
        ->set('travel_purpose', 'Koordinasi dengan PPK')
        ->set('travel_departure_date', '2026-03-05')
        ->set('travel_return_date', '2026-03-01')
        ->call('saveTravel')
        ->assertHasErrors(['travel_return_date']);
});

it('edits and deletes a travel assignment', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeAssignmentAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $personnel = Personnel::factory()->create(['organization_id' => $organization->id]);
    $travel = TravelAssignment::factory()->create([
        'project_id' => $project->id,
        'personnel_id' => $personnel->id,
        'destination' => 'Balikpapan',
    ]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->call('editTravel', $travel->id)
        ->set('travel_destination', 'Bontang')
        ->call('saveTravel')
        ->assertHasNoErrors();

    expect($travel->fresh()->destination)->toBe('Bontang');

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->call('deleteTravel', $travel->id);

    expect($project->travelAssignments()->count())->toBe(0);
});
