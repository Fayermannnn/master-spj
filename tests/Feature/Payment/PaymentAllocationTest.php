<?php

declare(strict_types=1);

use App\Domain\Cost\Services\BudgetRealizationService;
use App\Domain\Cost\Services\CostItemService;
use App\Domain\Payment\Services\PaymentAllocationService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\CostCategory;
use App\Models\CostItem;
use App\Models\Payment;
use App\Models\Project;

it('allocates a payment amount to a cost item of the same project', function (): void {
    $project = Project::factory()->create();
    $payment = Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 1, 'amount' => 10_000_000]);
    $costItem = CostItem::factory()->create(['project_id' => $project->id, 'total' => 15_000_000]);

    $allocation = app(PaymentAllocationService::class)->allocate($payment, $costItem, 6_000_000, 'Termin 1');

    expect($allocation->amount)->toEqual('6000000.00');
    expect($payment->allocations()->count())->toBe(1);
});

it('rejects allocating a cost item from a different project', function (): void {
    $payment = Payment::factory()->create(['termin_number' => 1, 'amount' => 10_000_000]);
    $costItem = CostItem::factory()->create();

    app(PaymentAllocationService::class)->allocate($payment, $costItem, 1_000_000, null);
})->throws(DomainActionException::class);

it('rejects allocating the same cost item twice to the same payment', function (): void {
    $project = Project::factory()->create();
    $payment = Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 1, 'amount' => 10_000_000]);
    $costItem = CostItem::factory()->create(['project_id' => $project->id]);

    $service = app(PaymentAllocationService::class);
    $service->allocate($payment, $costItem, 1_000_000, null);
    $service->allocate($payment, $costItem, 1_000_000, null);
})->throws(DomainActionException::class);

it('rejects an allocation that would exceed the remaining payment amount', function (): void {
    $project = Project::factory()->create();
    $payment = Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 1, 'amount' => 5_000_000]);
    $itemA = CostItem::factory()->create(['project_id' => $project->id]);
    $itemB = CostItem::factory()->create(['project_id' => $project->id]);

    $service = app(PaymentAllocationService::class);
    $service->allocate($payment, $itemA, 4_000_000, null);
    $service->allocate($payment, $itemB, 2_000_000, null);
})->throws(DomainActionException::class);

it('removes an allocation', function (): void {
    $project = Project::factory()->create();
    $payment = Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 1, 'amount' => 10_000_000]);
    $costItem = CostItem::factory()->create(['project_id' => $project->id]);

    $service = app(PaymentAllocationService::class);
    $allocation = $service->allocate($payment, $costItem, 1_000_000, null);
    $service->removeAllocation($allocation);

    expect($payment->allocations()->count())->toBe(0);
});

it('blocks deleting a cost item that already has an allocation', function (): void {
    $project = Project::factory()->create();
    $payment = Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 1, 'amount' => 10_000_000]);
    $costItem = CostItem::factory()->create(['project_id' => $project->id]);
    app(PaymentAllocationService::class)->allocate($payment, $costItem, 1_000_000, null);

    app(CostItemService::class)->delete($costItem);
})->throws(DomainActionException::class);

it('computes budget realization per cost category, including over-realization above 100%', function (): void {
    $project = Project::factory()->create();
    $categoryA = CostCategory::factory()->create(['name' => 'Personel']);
    $categoryB = CostCategory::factory()->create(['name' => 'Operasional']);

    $itemA = CostItem::factory()->create(['project_id' => $project->id, 'cost_category_id' => $categoryA->id, 'total' => 10_000_000]);
    $itemB = CostItem::factory()->create(['project_id' => $project->id, 'cost_category_id' => $categoryB->id, 'total' => 4_000_000]);

    $paymentA = Payment::factory()->create(['project_id' => $project->id, 'termin_number' => 1, 'amount' => 20_000_000]);
    $service = app(PaymentAllocationService::class);
    $service->allocate($paymentA, $itemA, 5_000_000, null);
    $service->allocate($paymentA, $itemB, 5_000_000, null);

    $rows = app(BudgetRealizationService::class)->rows($project)->keyBy('category_name');

    expect($rows['Personel']['budgeted'])->toBe(10_000_000.0);
    expect($rows['Personel']['realized'])->toBe(5_000_000.0);
    expect($rows['Personel']['percentage'])->toBe(50);

    expect($rows['Operasional']['budgeted'])->toBe(4_000_000.0);
    expect($rows['Operasional']['realized'])->toBe(5_000_000.0);
    expect($rows['Operasional']['percentage'])->toBe(125);

    $totals = app(BudgetRealizationService::class)->totals($project);
    expect($totals['budgeted'])->toBe(14_000_000.0);
    expect($totals['realized'])->toBe(10_000_000.0);
});
