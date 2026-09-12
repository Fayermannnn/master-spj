<?php

declare(strict_types=1);

namespace App\Domain\DocumentTemplate\Enums;

/**
 * Siklus versi template (§63 master prompt). Maksimal SATU versi
 * berstatus Active per DocumentRequirement — dijaga oleh
 * DocumentTemplateService::activate(), bukan constraint database
 * (butuh partial unique index yang tidak portable antar driver).
 */
enum TemplateStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Aktif',
            self::Archived => 'Diarsipkan',
        };
    }
}
