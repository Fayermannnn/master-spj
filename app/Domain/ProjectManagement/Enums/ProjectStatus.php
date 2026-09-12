<?php

declare(strict_types=1);

namespace App\Domain\ProjectManagement\Enums;

/**
 * Status siklus project (RULE §9 master prompt). Transisi yang diizinkan
 * divalidasi di ProjectService, bukan di sini — enum ini hanya daftar
 * status yang sah.
 */
enum ProjectStatus: string
{
    case Draft = 'draft';
    case Preparation = 'preparation';
    case Active = 'active';
    case PaymentProcessing = 'payment_processing';
    case Completed = 'completed';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Preparation => 'Persiapan',
            self::Active => 'Aktif',
            self::PaymentProcessing => 'Proses Pembayaran',
            self::Completed => 'Selesai',
            self::Closed => 'Ditutup',
            self::Archived => 'Diarsipkan',
        };
    }

    /**
     * Peta transisi status yang diizinkan. Kunci = status asal, nilai =
     * daftar status tujuan yang sah dari status tersebut.
     *
     * @return array<string, list<string>>
     */
    public static function allowedTransitions(): array
    {
        return [
            self::Draft->value => [self::Preparation->value, self::Archived->value],
            self::Preparation->value => [self::Active->value, self::Draft->value, self::Archived->value],
            self::Active->value => [self::PaymentProcessing->value, self::Completed->value],
            self::PaymentProcessing->value => [self::Active->value, self::Completed->value],
            self::Completed->value => [self::Closed->value, self::Active->value],
            self::Closed->value => [self::Archived->value],
            self::Archived->value => [],
        ];
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target->value, self::allowedTransitions()[$this->value], true);
    }
}
