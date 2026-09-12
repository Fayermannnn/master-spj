<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Domain\ProjectManagement\Enums\ProjectStatus;
use App\Domain\ProjectManagement\Services\ProjectService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Livewire\Projects\Form;
use App\Livewire\Projects\Show;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use Livewire\Livewire;

function makeProjectAdminUser(Organization $organization): User
{
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    return $user;
}

it('creates a project scoped to the actor organization', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeProjectAdminUser($organization);
    $projectType = ProjectType::factory()->create();
    $client = Client::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('project_type_id', $projectType->id)
        ->set('client_id', $client->id)
        ->set('code', 'PROJ-001')
        ->set('name', 'Project Percobaan')
        ->call('save')
        ->assertHasNoErrors();

    $project = Project::query()->where('code', 'PROJ-001')->firstOrFail();
    expect($project->organization_id)->toBe($organization->id);
    expect($project->status)->toBe(ProjectStatus::Draft);
});

it('forces organization_id to the actor own organization even if tampered client-side', function (): void {
    $ownOrganization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $admin = makeProjectAdminUser($ownOrganization);
    $projectType = ProjectType::factory()->create();
    $client = Client::factory()->create(['organization_id' => $ownOrganization->id]);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('organization_id', $otherOrganization->id)
        ->set('project_type_id', $projectType->id)
        ->set('client_id', $client->id)
        ->set('code', 'PROJ-TAMPER')
        ->set('name', 'Project Tamper')
        ->call('save');

    $project = Project::query()->where('code', 'PROJ-TAMPER')->firstOrFail();
    expect($project->organization_id)->toBe($ownOrganization->id);
});

it('allows a valid status transition and rejects an invalid one', function (): void {
    $organization = Organization::factory()->create();
    $project = Project::factory()->create([
        'organization_id' => $organization->id,
        'status' => ProjectStatus::Draft->value,
    ]);
    $admin = makeProjectAdminUser($organization);

    Livewire::actingAs($admin)
        ->test(Show::class, ['project' => $project])
        ->call('transitionTo', ProjectStatus::Preparation->value)
        ->assertHasNoErrors();

    expect($project->fresh()->status)->toBe(ProjectStatus::Preparation);

    Livewire::actingAs($admin)
        ->test(Show::class, ['project' => $project])
        ->call('transitionTo', ProjectStatus::Completed->value)
        ->assertHasErrors(['status']);

    expect($project->fresh()->status)->toBe(ProjectStatus::Preparation);
});

it('blocks deleting a project once it has left draft status', function (): void {
    $organization = Organization::factory()->create();
    $project = Project::factory()->create([
        'organization_id' => $organization->id,
        'status' => ProjectStatus::Active->value,
    ]);

    expect(fn () => app(ProjectService::class)->delete($project))
        ->toThrow(DomainActionException::class);
});

it('prevents viewing a project that belongs to another organization', function (): void {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $admin = makeProjectAdminUser($organization);
    $otherProject = Project::factory()->create(['organization_id' => $otherOrganization->id]);

    Livewire::actingAs($admin)
        ->test(Show::class, ['project' => $otherProject])
        ->assertForbidden();
});
