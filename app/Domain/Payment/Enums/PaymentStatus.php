<?php

declare(strict_types=1);

namespace App\Domain\Payment\Enums;

/**
 * Status siklus termin/pembayaran. Sama seperti ProjectStatus (lihat
 * PROJECT_DECISIONS.md D-010) — enum + peta transisi, bukan FSM generik.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Belum Diajukan',
            self::Submitted => 'Diajukan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Paid => 'Dibayar',
        };
    }

    /**
     * @return array<string, list<string>>
     */
    public static function allowedTransitions(): array
    {
        return [
            self::Pending->value => [self::Submitted->value],
            self::Submitted->value => [self::Approved->value, self::Rejected->value],
            self::Approved->value => [self::Paid->value, self::Rejected->value],
            self::Rejected->value => [self::Pending->value],
            self::Paid->value => [],
        ];
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target->value, self::allowedTransitions()[$this->value], true);
    }
}
