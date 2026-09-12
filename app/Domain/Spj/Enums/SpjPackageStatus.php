<?php

declare(strict_types=1);

namespace App\Domain\Spj\Enums;

/**
 * Siklus paket SPJ. Draft bisa terus ditambah/dihapus isinya; Finalized
 * mengunci manifest (mirip TemplateStatus Active — "versi yang berlaku"
 * tidak boleh diam-diam berubah isinya) supaya paket yang sudah
 * diserahkan/diekspor tidak bisa disusupi item baru tanpa jejak. Tidak
 * ada jalan balik ke Draft — buat paket baru kalau perlu revisi (pola
 * sama dengan versioning DocumentTemplate/Document, PROJECT_DECISIONS.md
 * D-016/D-018).
 */
enum SpjPackageStatus: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Finalized => 'Final',
        };
    }
}
