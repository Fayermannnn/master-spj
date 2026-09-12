<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Livewire\ProjectPayments\Manager;
use App\Models\Contract;
use App\Models\CostItem;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

function makePaymentAdmin(Organization $organization): User
{
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    return $user;
}

it('creates a termin within the contract value', function (): void {
    $organization = Organization::factory()->create();
    $admin = makePaymentAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    Contract::factory()->create(['project_id' => $project->id, 'contract_value' => 100_000_000]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->set('name', 'Termin 1')
        ->set('amount', '30000000')
        ->call('save')
        ->assertHasNoErrors();

    expect(Payment::query()->where('project_id', $project->id)->count())->toBe(1);
});

it('rejects a termin that would exceed the contract value without an override', function (): void {
    $organization = Organization::factory()->create();
    $admin = makePaymentAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    Contract::factory()->create(['project_id' => $project->id, 'contract_value' => 100_000_000]);
    Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 1, 'amount' => 80_000_000]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->set('name', 'Termin 2')
        ->set('amount', '30000000')
        ->call('save')
        ->assertHasErrors(['amount']);

    expect(Payment::query()->where('project_id', $project->id)->count())->toBe(1);
});

it('allows exceeding the contract value when the override flag is set', function (): void {
    $organization = Organization::factory()->create();
    $admin = makePaymentAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    Contract::factory()->create(['project_id' => $project->id, 'contract_value' => 100_000_000]);
    Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 1, 'amount' => 80_000_000]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->set('name', 'Termin 2')
        ->set('amount', '30000000')
        ->set('override_limit', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Payment::query()->where('project_id', $project->id)->count())->toBe(2);
});

it('allows a valid payment status transition and rejects an invalid one', function (): void {
    $organization = Organization::factory()->create();
    $admin = makePaymentAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $payment = Payment::factory()->create([
        'project_id' => $project->id,
        'termin_number' => 1,
        'status' => PaymentStatus::Pending->value,
    ]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->call('transitionTo', $payment->id, PaymentStatus::Submitted->value)
        ->assertHasNoErrors();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Submitted);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->call('transitionTo', $payment->id, PaymentStatus::Paid->value)
        ->assertHasErrors(['status']);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Submitted);
});

it('prevents two termin from sharing the same termin_number on one project', function (): void {
    $organization = Organization::factory()->create();
    $admin = makePaymentAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 1]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->set('termin_number', '1')
        ->set('name', 'Termin Duplikat')
        ->set('amount', '10000000')
        ->call('save')
        ->assertHasErrors(['termin_number']);
});

it('allocates a payment to a cost item through the Livewire manager and rejects over-allocation', function (): void {
    $organization = Organization::factory()->create();
    $admin = makePaymentAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $payment = Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 1, 'amount' => 5_000_000]);
    $costItem = CostItem::factory()->create(['project_id' => $project->id]);

    $component = Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->call('toggleAllocations', $payment->id)
        ->set('allocation_cost_item_id', $costItem->id)
        ->set('allocation_amount', '3000000')
        ->call('allocate', $payment->id)
        ->assertHasNoErrors();

    expect($payment->allocations()->count())->toBe(1);

    $component
        ->set('allocation_cost_item_id', $costItem->id)
        ->set('allocation_amount', '3000000')
        ->call('allocate', $payment->id)
        ->assertHasErrors(['allocation_amount']);

    expect($payment->allocations()->count())->toBe(1);
});

it('removes a payment allocation through the Livewire manager', function (): void {
    $organization = Organization::factory()->create();
    $admin = makePaymentAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $payment = Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 1, 'amount' => 5_000_000]);
    $costItem = CostItem::factory()->create(['project_id' => $project->id]);

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->set('allocation_cost_item_id', $costItem->id)
        ->set('allocation_amount', '3000000')
        ->call('allocate', $payment->id);

    $allocation = $payment->allocations()->firstOrFail();

    Livewire::actingAs($admin)
        ->test(Manager::class, ['project' => $project])
        ->call('removeAllocation', $payment->id, $allocation->id);

    expect($payment->allocations()->count())->toBe(0);
});

it('prevents a member from another organization from managing payments on someone else\'s project', function (): void {
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 1]);

    $otherOrganization = Organization::factory()->create();
    $outsider = makePaymentAdmin($otherOrganization);

    Livewire::actingAs($outsider)
        ->test(Manager::class, ['project' => $project])
        ->assertForbidden();
});
