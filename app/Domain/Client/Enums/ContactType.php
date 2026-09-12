<?php

declare(strict_types=1);

namespace App\Domain\Client\Enums;

enum ContactType: string
{
    case Ppk = 'ppk';
    case Pptk = 'pptk';
    case PaKpa = 'pa_kpa';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Ppk => 'PPK',
            self::Pptk => 'PPTK',
            self::PaKpa => 'PA/KPA',
            self::Other => 'Lainnya',
        };
    }
}
