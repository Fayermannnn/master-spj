<?php

declare(strict_types=1);

namespace App\Domain\Personnel\Enums;

enum PersonnelDocumentType: string
{
    case Ktp = 'ktp';
    case Npwp = 'npwp';
    case Cv = 'cv';
    case Ijazah = 'ijazah';
    case SkaSkk = 'ska_skk';
    case SuratPernyataan = 'surat_pernyataan';
    case SuratPenugasan = 'surat_penugasan';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Ktp => 'KTP',
            self::Npwp => 'NPWP',
            self::Cv => 'CV',
            self::Ijazah => 'Ijazah',
            self::SkaSkk => 'SKA/SKK',
            self::SuratPernyataan => 'Surat Pernyataan',
            self::SuratPenugasan => 'Surat Penugasan',
            self::Other => 'Lainnya',
        };
    }
}
