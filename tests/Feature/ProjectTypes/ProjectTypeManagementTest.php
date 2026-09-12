<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Domain\ProjectManagement\Services\ProjectTypeService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Livewire\ProjectTypes\Form;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use Livewire\Livewire;

function makeSuperAdminUser(): User
{
    $user = User::factory()->create();
    $user->syncRoles([RoleName::SuperAdmin->value]);

    return $user;
}

function makeAdminPerusahaanUser(Organization $organization): User
{
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    return $user;
}

it('allows super admin to create a project type', function (): void {
    $admin = makeSuperAdminUser();

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('code', 'CUSTOM_TYPE')
        ->set('name', 'Custom Type')
        ->call('save')
        ->assertHasNoErrors();

    expect(ProjectType::query()->where('code', 'CUSTOM_TYPE')->exists())->toBeTrue();
});

it('prevents a non super-admin from creating a project type', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeAdminPerusahaanUser($organization);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->assertForbidden();
});

it('allows a non super-admin to view the project type list (read-only picker source)', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeAdminPerusahaanUser($organization);

    $this->actingAs($admin);

    expect($admin->can('viewAny', ProjectType::class))->toBeTrue();
    expect($admin->can('create', ProjectType::class))->toBeFalse();
});

it('blocks deleting a project type still used by a project', function (): void {
    $superAdmin = makeSuperAdminUser();
    $projectType = ProjectType::factory()->create();
    Project::factory()->create(['project_type_id' => $projectType->id]);

    $this->actingAs($superAdmin);

    expect(fn () => app(ProjectTypeService::class)->delete($projectType))
        ->toThrow(DomainActionException::class);
});
