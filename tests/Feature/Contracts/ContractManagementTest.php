<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Livewire\Contracts\Form;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Project;
use App\Models\TaxType;
use App\Models\User;
use Livewire\Livewire;

function makeContractAdmin(Organization $organization): User
{
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    return $user;
}

it('creates a contract for a project that has none yet', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeContractAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($admin)
        ->test(Form::class, ['project' => $project])
        ->set('contract_number', '001/KTR/2026')
        ->set('contract_date', '2026-01-15')
        ->set('contract_value', '500000000')
        ->call('save')
        ->assertHasNoErrors();

    $contract = Contract::query()->where('project_id', $project->id)->firstOrFail();
    expect($contract->contract_number)->toBe('001/KTR/2026');
    expect((float) $contract->contract_value)->toBe(500_000_000.0);
});

it('updates the existing contract instead of creating a second one', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeContractAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    Contract::factory()->create(['project_id' => $project->id, 'contract_number' => 'OLD/001']);

    Livewire::actingAs($admin)
        ->test(Form::class, ['project' => $project])
        ->set('contract_number', 'NEW/002')
        ->set('contract_date', '2026-02-01')
        ->set('contract_value', '750000000')
        ->call('save')
        ->assertHasNoErrors();

    expect(Contract::query()->where('project_id', $project->id)->count())->toBe(1);
    expect(Contract::query()->where('project_id', $project->id)->first()->contract_number)->toBe('NEW/002');
});

it('computes a default tax amount and net value from the tax type rate', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeContractAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $tax = TaxType::factory()->create(['rate' => 11]);

    Livewire::actingAs($admin)
        ->test(Form::class, ['project' => $project])
        ->set('contract_value', '1110000')
        ->set('tax_type_id', $tax->id)
        ->assertSet('tax_amount', '110000')
        ->assertSet('net_value', '1000000');
});

it('rejects a negative contract value', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeContractAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($admin)
        ->test(Form::class, ['project' => $project])
        ->set('contract_number', '001/KTR/2026')
        ->set('contract_date', '2026-01-15')
        ->set('contract_value', '-100')
        ->call('save')
        ->assertHasErrors(['contract_value']);
});

it('ignores a contract_value submitted through the main form once a contract already exists', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeContractAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    Contract::factory()->create(['project_id' => $project->id, 'contract_value' => 500_000_000]);

    Livewire::actingAs($admin)
        ->test(Form::class, ['project' => $project])
        ->set('contract_value', '999999999')
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $project->contract()->first()->contract_value)->toBe(500_000_000.0);
});

it('adds a contract addendum that updates the effective contract value', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeContractAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    Contract::factory()->create(['project_id' => $project->id, 'contract_value' => 500_000_000]);

    Livewire::actingAs($admin)
        ->test(Form::class, ['project' => $project])
        ->call('openAddendumForm')
        ->set('addendum_number', '001/ADD/2026')
        ->set('addendum_date', '2026-03-01')
        ->set('reason', 'Perpanjangan waktu dan penambahan nilai pekerjaan')
        ->set('new_contract_value', '600000000')
        ->call('saveAddendum')
        ->assertHasNoErrors();

    $contract = $project->contract()->first();
    expect((float) $contract->contract_value)->toBe(600_000_000.0);
    expect($contract->addenda()->count())->toBe(1);
    expect((float) $contract->addenda()->first()->previous_value)->toBe(500_000_000.0);
});

it('allows an addendum with no value change (e.g. schedule-only amendment)', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeContractAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    Contract::factory()->create(['project_id' => $project->id, 'contract_value' => 500_000_000]);

    Livewire::actingAs($admin)
        ->test(Form::class, ['project' => $project])
        ->call('openAddendumForm')
        ->set('addendum_date', '2026-03-01')
        ->set('reason', 'Perpanjangan waktu pelaksanaan 30 hari kalender')
        ->call('saveAddendum')
        ->assertHasNoErrors();

    $contract = $project->contract()->first();
    expect((float) $contract->contract_value)->toBe(500_000_000.0);
    expect($contract->addenda()->first()->new_value)->toBeNull();
});

it('only allows deleting the most recent addendum and reverts the contract value', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeContractAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $contract = Contract::factory()->create(['project_id' => $project->id, 'contract_value' => 500_000_000]);

    $component = Livewire::actingAs($admin)->test(Form::class, ['project' => $project]);

    $component->call('openAddendumForm')
        ->set('addendum_date', '2026-01-01')
        ->set('reason', 'Adendum pertama')
        ->set('new_contract_value', '600000000')
        ->call('saveAddendum');

    $component->call('openAddendumForm')
        ->set('addendum_date', '2026-02-01')
        ->set('reason', 'Adendum kedua')
        ->set('new_contract_value', '700000000')
        ->call('saveAddendum');

    // Diurutkan dari yang PALING BARU DIBUAT (bukan addendum_date) —
    // lihat ContractAddendumService::delete().
    $latest = $contract->addenda()->orderByDesc('id')->firstOrFail();
    $oldest = $contract->addenda()->orderBy('id')->firstOrFail();

    $component->call('deleteAddendum', $latest->id);
    expect((float) $project->contract()->first()->contract_value)->toBe(600_000_000.0);
    expect($contract->addenda()->count())->toBe(1);

    $component->call('deleteAddendum', $oldest->id);
    expect((float) $project->contract()->first()->contract_value)->toBe(500_000_000.0);
    expect($contract->addenda()->count())->toBe(0);
});

it('prevents a member from another organization from managing the contract on someone else\'s project', function (): void {
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);

    $otherOrganization = Organization::factory()->create();
    $outsider = makeContractAdmin($otherOrganization);

    Livewire::actingAs($outsider)
        ->test(Form::class, ['project' => $project])
        ->assertForbidden();
});
