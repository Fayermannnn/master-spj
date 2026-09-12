<?php

declare(strict_types=1);

use App\Domain\Cost\Services\TaxTypeService;
use App\Domain\Identity\Enums\RoleName;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Livewire\TaxTypes\Form;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Project;
use App\Models\TaxType;
use App\Models\User;
use Livewire\Livewire;

function makeTaxSuperAdmin(): User
{
    $user = User::factory()->create();
    $user->syncRoles([RoleName::SuperAdmin->value]);

    return $user;
}

it('allows super admin to create a tax type', function (): void {
    $admin = makeTaxSuperAdmin();

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('code', 'CUSTOM_TAX')
        ->set('name', 'Custom Tax')
        ->set('rate', '7.5')
        ->call('save')
        ->assertHasNoErrors();

    expect(TaxType::query()->where('code', 'CUSTOM_TAX')->exists())->toBeTrue();
});

it('prevents a non super-admin from creating a tax type', function (): void {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->syncRoles([RoleName::AdminPerusahaan->value]);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->assertForbidden();
});

it('blocks deleting a tax type still used by a contract', function (): void {
    $taxType = TaxType::factory()->create();
    $project = Project::factory()->create();
    Contract::factory()->create(['project_id' => $project->id, 'tax_type_id' => $taxType->id]);

    expect(fn () => app(TaxTypeService::class)->delete($taxType))
        ->toThrow(DomainActionException::class);
});
