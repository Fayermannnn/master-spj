<?php

declare(strict_types=1);

use App\Domain\Cost\Services\CostCategoryService;
use App\Domain\Identity\Enums\RoleName;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Livewire\CostCategories\Form;
use App\Models\CostCategory;
use App\Models\CostItem;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('allows super admin to create a cost category', function (): void {
    $admin = User::factory()->create();
    $admin->syncRoles([RoleName::SuperAdmin->value]);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('code', 'CUSTOM_COST')
        ->set('name', 'Custom Cost')
        ->call('save')
        ->assertHasNoErrors();

    expect(CostCategory::query()->where('code', 'CUSTOM_COST')->exists())->toBeTrue();
});

it('prevents a non super-admin from creating a cost category', function (): void {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->syncRoles([RoleName::AdminPerusahaan->value]);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->assertForbidden();
});

it('blocks deleting a cost category still used by a cost item', function (): void {
    $category = CostCategory::factory()->create();
    CostItem::factory()->create(['cost_category_id' => $category->id]);

    expect(fn () => app(CostCategoryService::class)->delete($category))
        ->toThrow(DomainActionException::class);
});
