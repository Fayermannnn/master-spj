<?php

declare(strict_types=1);

namespace App\Domain\Workplan\Enums;

/**
 * Status dipakai bersama oleh Milestone dan Deliverable — tiga status
 * sederhana yang cukup untuk timeline (Pending/InProgress/Completed),
 * bukan FSM generik (pola sama D-010: 7 status project pun cukup
 * dengan enum + peta transisi, bukan mesin FSM terpisah). "Terlambat"
 * SENGAJA tidak jadi status tersimpan — dihitung dinamis dari
 * target_date vs hari ini (lihat `Milestone::isOverdue()`), supaya
 * tidak perlu job terjadwal untuk mengubah status diam-diam.
 */
enum WorkplanStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Belum Dimulai',
            self::InProgress => 'Sedang Berjalan',
            self::Completed => 'Selesai',
        };
    }
}
