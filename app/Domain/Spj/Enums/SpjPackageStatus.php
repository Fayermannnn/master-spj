<?php

declare(strict_types=1);

namespace App\Domain\Spj\Enums;

/**
 * Siklus paket SPJ — Draft -> Submitted -> Finalized, dengan Submitted
 * bisa ditolak kembali ke Draft (pola sama PaymentStatus/ProjectStatus,
 * PROJECT_DECISIONS.md D-010): enum + peta transisi, divalidasi di
 * service, bukan FSM generik. Draft & Submitted SAMA-SAMA mengunci
 * manifest kecuali Draft (lihat SpjPackageService::assertDraft()) —
 * begitu diajukan untuk review, manifest tidak bisa diam-diam berubah
 * sebelum direview. Finalized adalah TERMINAL — tidak ada jalan balik,
 * buat paket baru untuk revisi (pola sama versioning
 * DocumentTemplate/Document, D-016/D-018). Lihat D-028 untuk konteks
 * penambahan status Submitted.
 */
enum SpjPackageStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Finalized = 'finalized';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Menunggu Review',
            self::Finalized => 'Final',
        };
    }

    /**
     * @return array<string, list<string>>
     */
    public static function allowedTransitions(): array
    {
        return [
            self::Draft->value => [self::Submitted->value],
            self::Submitted->value => [self::Finalized->value, self::Draft->value],
            self::Finalized->value => [],
        ];
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target->value, self::allowedTransitions()[$this->value], true);
    }
}
