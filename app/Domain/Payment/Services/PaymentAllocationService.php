<?php

declare(strict_types=1);

namespace App\Domain\Payment\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\CostItem;
use App\Models\Payment;
use App\Models\PaymentItem;

/**
 * @domain Payment
 *
 * Mengalokasikan nominal Payment (termin) ke CostItem — dasar
 * perhitungan realisasi anggaran per kategori biaya
 * (PROJECT_DECISIONS.md D-025, lihat juga BudgetRealizationService di
 * domain Cost). Sengaja TIDAK membatasi realisasi terhadap sisa
 * anggaran CostItem — realisasi melebihi rencana adalah sinyal yang
 * SEHARUSNYA muncul di laporan, bukan sesuatu yang perlu diblokir di
 * sini (RULE 67, jangan menambah aturan yang tidak diminta).
 */
class PaymentAllocationService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function allocate(Payment $payment, CostItem $costItem, float $amount, ?string $notes): PaymentItem
    {
        if ($costItem->project_id !== $payment->project_id) {
            throw new DomainActionException('Item biaya harus berasal dari project yang sama dengan termin ini.');
        }

        if ($payment->allocations()->where('cost_item_id', $costItem->id)->exists()) {
            throw new DomainActionException('Item biaya ini sudah dialokasikan pada termin ini. Hapus alokasi yang ada untuk mengubah nominalnya.');
        }

        $existingTotal = (float) $payment->allocations()->sum('amount');
        $paymentAmount = (float) $payment->amount;

        if ($existingTotal + $amount > $paymentAmount) {
            $remaining = max($paymentAmount - $existingTotal, 0);
            $formattedRemaining = 'Rp '.number_format($remaining, 0, ',', '.');

            throw new DomainActionException("Alokasi melebihi sisa nominal termin ini (tersisa {$formattedRemaining}).");
        }

        $allocation = $payment->allocations()->create([
            'cost_item_id' => $costItem->id,
            'amount' => $amount,
            'notes' => $notes,
        ]);

        $this->auditLog->record('Payment', 'item_allocated', $payment, after: [
            'cost_item_id' => $costItem->id,
            'amount' => $amount,
        ]);

        return $allocation;
    }

    public function removeAllocation(PaymentItem $allocation): void
    {
        $payment = $allocation->payment;
        $before = $allocation->getAttributes();

        $allocation->delete();

        $this->auditLog->record('Payment', 'item_allocation_removed', $payment, before: $before);
    }
}
