<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Domain\Personnel\Services\PersonnelCategoryService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Livewire\PersonnelCategories\Form;
use App\Models\Organization;
use App\Models\Personnel;
use App\Models\PersonnelCategory;
use App\Models\User;
use Livewire\Livewire;

function makePersonnelSuperAdmin(): User
{
    $user = User::factory()->create();
    $user->syncRoles([RoleName::SuperAdmin->value]);

    return $user;
}

function makePersonnelOrgAdmin(Organization $organization): User
{
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    return $user;
}

it('allows super admin to create a personnel category', function (): void {
    $admin = makePersonnelSuperAdmin();

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('code', 'CUSTOM_CATEGORY')
        ->set('name', 'Custom Category')
        ->call('save')
        ->assertHasNoErrors();

    expect(PersonnelCategory::query()->where('code', 'CUSTOM_CATEGORY')->exists())->toBeTrue();
});

it('prevents a non super-admin from creating a personnel category', function (): void {
    $organization = Organization::factory()->create();
    $admin = makePersonnelOrgAdmin($organization);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->assertForbidden();
});

it('blocks deleting a personnel category still used by personnel', function (): void {
    $category = PersonnelCategory::factory()->create();
    Personnel::factory()->create(['personnel_category_id' => $category->id]);

    expect(fn () => app(PersonnelCategoryService::class)->delete($category))
        ->toThrow(DomainActionException::class);
});
