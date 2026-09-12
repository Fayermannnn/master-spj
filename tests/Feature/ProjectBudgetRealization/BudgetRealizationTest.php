<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Domain\Payment\Services\PaymentAllocationService;
use App\Livewire\ProjectBudgetRealization\Manager;
use App\Models\CostItem;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

it('shows budget realization rows for a project member', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);
    $project = Project::factory()->create(['organization_id' => $organization->id]);

    $costItem = CostItem::factory()->create(['project_id' => $project->id, 'total' => 1_000_000]);
    $payment = Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 1, 'amount' => 1_000_000]);
    app(PaymentAllocationService::class)->allocate($payment, $costItem, 500_000, null);

    Livewire::actingAs($user)
        ->test(Manager::class, ['project' => $project])
        ->assertViewHas('totals', fn (array $totals): bool => $totals['realized'] === 500_000.0);
});

it('prevents a member from another organization from viewing budget realization', function (): void {
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);

    $otherOrganization = Organization::factory()->create();
    $outsider = User::factory()->create(['organization_id' => $otherOrganization->id]);
    $outsider->syncRoles([RoleName::AdminPerusahaan->value]);

    Livewire::actingAs($outsider)
        ->test(Manager::class, ['project' => $project])
        ->assertForbidden();
});
