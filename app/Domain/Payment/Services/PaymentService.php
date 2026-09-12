<?php

declare(strict_types=1);

namespace App\Domain\Payment\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\Payment;
use App\Models\Project;

/**
 * @domain Payment
 *
 * RULE 57 master prompt: "Payment amount tidak boleh melebihi remaining
 * contract value tanpa explicit override" — divalidasi di sini, bukan
 * cuma di form, supaya berlaku juga untuk pemanggil lain di masa depan.
 */
class PaymentService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Project $project, array $data, bool $overrideLimit = false): Payment
    {
        $this->assertWithinContractLimit($project, (float) $data['amount'], null, $overrideLimit);

        $data['status'] ??= PaymentStatus::Pending->value;

        $payment = $project->payments()->create($data);

        $this->auditLog->record('Payment', 'created', $payment, after: $payment->getAttributes());

        return $payment;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Payment $payment, array $data, bool $overrideLimit = false): Payment
    {
        $this->assertWithinContractLimit($payment->project, (float) $data['amount'], $payment->id, $overrideLimit);

        $before = $payment->getAttributes();

        $payment->update($data);

        $this->auditLog->record('Payment', 'updated', $payment, before: $before, after: $payment->getChanges());

        return $payment;
    }

    public function delete(Payment $payment): void
    {
        $before = $payment->getAttributes();

        $payment->delete();

        $this->auditLog->record('Payment', 'deleted', $payment, before: $before);
    }

    public function transitionStatus(Payment $payment, PaymentStatus $target): Payment
    {
        if (! $payment->status->canTransitionTo($target)) {
            throw new DomainActionException(
                "Status termin tidak dapat diubah dari \"{$payment->status->label()}\" ke \"{$target->label()}\"."
            );
        }

        $before = $payment->status->value;

        $payment->update(['status' => $target->value]);

        $this->auditLog->record('Payment', 'status_transitioned', $payment, before: ['status' => $before], after: ['status' => $target->value]);

        return $payment;
    }

    private function assertWithinContractLimit(?Project $project, float $amount, ?string $excludingPaymentId, bool $overrideLimit): void
    {
        if ($overrideLimit || ! $project) {
            return;
        }

        $contract = $project->contract;

        if (! $contract) {
            return;
        }

        $existingTotal = (float) $project->payments()
            ->when($excludingPaymentId, fn ($query) => $query->where('id', '!=', $excludingPaymentId))
            ->sum('amount');

        $contractValue = (float) $contract->contract_value;

        if ($existingTotal + $amount > $contractValue) {
            $formattedContractValue = 'Rp '.number_format($contractValue, 0, ',', '.');

            throw new DomainActionException(
                "Total pembayaran akan melebihi nilai kontrak ({$formattedContractValue}). Centang \"izinkan melebihi nilai kontrak\" jika ini memang disengaja."
            );
        }
    }
}
