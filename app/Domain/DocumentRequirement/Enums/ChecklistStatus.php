<?php

declare(strict_types=1);

namespace App\Domain\DocumentRequirement\Enums;

/**
 * Status kelengkapan satu item checklist SPJ untuk satu project. Di
 * Phase 5 masih di-toggle manual; fase Document Generator/SPJ Package
 * (§25 master prompt) akan mengisi status ini otomatis dari
 * Document/Evidence begitu domain itu ada.
 */
enum ChecklistStatus: string
{
    case Missing = 'missing';
    case Fulfilled = 'fulfilled';
    case NotApplicable = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::Missing => 'Belum Lengkap',
            self::Fulfilled => 'Lengkap',
            self::NotApplicable => 'Tidak Berlaku',
        };
    }
}
