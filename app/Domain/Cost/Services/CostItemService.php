<?php

declare(strict_types=1);

namespace App\Domain\Cost\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\CostItem;
use App\Models\Project;
use App\Models\TaxType;

/**
 * @domain Cost
 *
 * Menghitung subtotal/pajak/total dari quantity, unit_price, dan (jika
 * ada) TaxType.rate — lihat §59/§60 master prompt: sistem mengotomatisasi
 * berdasarkan konfigurasi, tidak mengklaim kepatuhan pajak.
 */
class CostItemService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Project $project, array $data): CostItem
    {
        $data = $this->applyCalculation($data);

        $costItem = $project->costItems()->create($data);

        $this->auditLog->record('Cost', 'item_created', $costItem, after: $costItem->getAttributes());

        return $costItem;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CostItem $costItem, array $data): CostItem
    {
        $before = $costItem->getAttributes();

        $data = $this->applyCalculation($data);

        $costItem->update($data);

        $this->auditLog->record('Cost', 'item_updated', $costItem, before: $before, after: $costItem->getChanges());

        return $costItem;
    }

    public function delete(CostItem $costItem): void
    {
        if ($costItem->allocations()->exists()) {
            throw new DomainActionException(
                "Item biaya \"{$costItem->description}\" sudah memiliki alokasi pembayaran dan tidak dapat dihapus. Hapus alokasinya terlebih dahulu."
            );
        }

        $before = $costItem->getAttributes();

        $costItem->delete();

        $this->auditLog->record('Cost', 'item_deleted', $costItem, before: $before);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyCalculation(array $data): array
    {
        $lineAmount = round(((float) $data['quantity']) * ((float) $data['unit_price']), 2);
        $rate = 0.0;

        if (! empty($data['tax_type_id'])) {
            $taxType = TaxType::query()->whereKey($data['tax_type_id'])->first();
            $rate = $taxType ? (float) $taxType->rate : 0.0;
        }

        $isInclusive = ! empty($data['is_tax_inclusive']) && $rate > 0;

        if ($rate <= 0) {
            $data['subtotal'] = $lineAmount;
            $data['tax_amount'] = 0;
            $data['total'] = $lineAmount;

            return $data;
        }

        if ($isInclusive) {
            $subtotal = round($lineAmount / (1 + $rate / 100), 2);
            $data['subtotal'] = $subtotal;
            $data['tax_amount'] = round($lineAmount - $subtotal, 2);
            $data['total'] = $lineAmount;
        } else {
            $taxAmount = round($lineAmount * $rate / 100, 2);
            $data['subtotal'] = $lineAmount;
            $data['tax_amount'] = $taxAmount;
            $data['total'] = round($lineAmount + $taxAmount, 2);
        }

        return $data;
    }
}
