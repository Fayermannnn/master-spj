<?php

declare(strict_types=1);

namespace App\Domain\Notification\Enums;

/**
 * Jenis alert yang dikenali `NotificationService` — kosakata tertutup,
 * pola sama dengan `RequirementRuleField` (D-015): menambah jenis alert
 * baru berarti menambah satu case di sini + satu cabang di
 * `NotificationService::pending()`, bukan sistem trigger generik.
 */
enum NotificationType: string
{
    case CertificateExpiring = 'certificate_expiring';
    case MilestoneOverdue = 'milestone_overdue';
    case PaymentOverdue = 'payment_overdue';
    case ChecklistIncomplete = 'checklist_incomplete';

    public function label(): string
    {
        return match ($this) {
            self::CertificateExpiring => 'Sertifikat Akan/Sudah Kadaluarsa',
            self::MilestoneOverdue => 'Milestone Terlambat',
            self::PaymentOverdue => 'Termin Terlambat',
            self::ChecklistIncomplete => 'Checklist Belum Lengkap',
        };
    }
}
