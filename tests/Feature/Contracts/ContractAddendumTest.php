<?php

declare(strict_types=1);

use App\Domain\Contract\Services\ContractAddendumService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\Contract;

it('rejects deleting an addendum that is not the most recent one', function (): void {
    $contract = Contract::factory()->create(['contract_value' => 500_000_000]);
    $service = app(ContractAddendumService::class);

    $first = $service->create($contract, [
        'addendum_number' => null,
        'addendum_date' => '2026-01-01',
        'reason' => 'Adendum pertama',
        'new_value' => 600_000_000,
    ], null);

    $service->create($contract->fresh(), [
        'addendum_number' => null,
        'addendum_date' => '2026-02-01',
        'reason' => 'Adendum kedua',
        'new_value' => 700_000_000,
    ], null);

    $service->delete($first);
})->throws(DomainActionException::class);

it('keeps the contract value unchanged when the deleted addendum did not change the value', function (): void {
    $contract = Contract::factory()->create(['contract_value' => 500_000_000]);
    $service = app(ContractAddendumService::class);

    $addendum = $service->create($contract, [
        'addendum_number' => null,
        'addendum_date' => '2026-01-01',
        'reason' => 'Perpanjangan waktu saja',
        'new_value' => null,
    ], null);

    $service->delete($addendum);

    expect((float) $contract->fresh()->contract_value)->toBe(500_000_000.0);
    expect($contract->addenda()->count())->toBe(0);
});
