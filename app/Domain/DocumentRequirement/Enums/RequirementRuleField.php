<?php

declare(strict_types=1);

namespace App\Domain\DocumentRequirement\Enums;

/**
 * Kosakata field yang BOLEH dipakai rule — sengaja tertutup (bukan
 * dot-path bebas ke model manapun) supaya rule engine tetap sederhana &
 * aman (§18 master prompt: "jangan membuat expression engine yang
 * terlalu kompleks"). Nilai tiap field dihitung oleh
 * RequirementRuleEvaluator dari Project + relasinya, bukan dibaca
 * langsung lewat reflection.
 */
enum RequirementRuleField: string
{
    case ProjectTypeCode = 'project_type_code';
    case HasPersonnelAssignments = 'has_personnel_assignments';
    case HasPayments = 'has_payments';
    case HasTravelCost = 'has_travel_cost';
    case PersonnelCategoryCodes = 'personnel_category_codes';
    case PaymentCount = 'payment_count';

    public function label(): string
    {
        return match ($this) {
            self::ProjectTypeCode => 'Kode Jenis Project',
            self::HasPersonnelAssignments => 'Memiliki Penugasan Personel',
            self::HasPayments => 'Memiliki Termin/Pembayaran',
            self::HasTravelCost => 'Memiliki Biaya Perjalanan',
            self::PersonnelCategoryCodes => 'Kategori Personel yang Ditugaskan',
            self::PaymentCount => 'Jumlah Termin',
        };
    }

    /**
     * Tipe nilai yang dihasilkan field ini, dipakai untuk membatasi
     * operator yang valid — lihat RequirementRuleOperator::validFor().
     */
    public function valueType(): string
    {
        return match ($this) {
            self::ProjectTypeCode => 'string',
            self::HasPersonnelAssignments, self::HasTravelCost, self::HasPayments => 'bool',
            self::PersonnelCategoryCodes => 'array',
            self::PaymentCount => 'int',
        };
    }
}
