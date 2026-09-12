<?php

declare(strict_types=1);

namespace App\Domain\DocumentRequirement\Enums;

enum RequirementRuleOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case Contains = 'contains';
    case IsTrue = 'is_true';
    case IsFalse = 'is_false';
    case GreaterThan = 'greater_than';
    case LessThan = 'less_than';

    public function label(): string
    {
        return match ($this) {
            self::Equals => 'Sama Dengan',
            self::NotEquals => 'Tidak Sama Dengan',
            self::Contains => 'Mengandung',
            self::IsTrue => 'Benar (Ya)',
            self::IsFalse => 'Salah (Tidak)',
            self::GreaterThan => 'Lebih Besar Dari',
            self::LessThan => 'Lebih Kecil Dari',
        };
    }

    /**
     * Operator mana yang valid untuk tipe nilai field tertentu — dipakai
     * validasi form supaya admin tidak bisa memasang kombinasi yang
     * tidak masuk akal (mis. "is_true" pada field string).
     *
     * @return list<self>
     */
    public static function validFor(string $valueType): array
    {
        return match ($valueType) {
            'bool' => [self::IsTrue, self::IsFalse],
            'array' => [self::Contains],
            'int' => [self::Equals, self::NotEquals, self::GreaterThan, self::LessThan],
            default => [self::Equals, self::NotEquals],
        };
    }

    /** Apakah operator ini butuh kolom `value` diisi. */
    public function needsValue(): bool
    {
        return ! in_array($this, [self::IsTrue, self::IsFalse], true);
    }
}
