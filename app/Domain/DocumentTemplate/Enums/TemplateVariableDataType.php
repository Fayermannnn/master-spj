<?php

declare(strict_types=1);

namespace App\Domain\DocumentTemplate\Enums;

/**
 * Tipe data placeholder yang didukung (§20/21 master prompt).
 */
enum TemplateVariableDataType: string
{
    case Text = 'text';
    case Number = 'number';
    case Currency = 'currency';
    case Date = 'date';
    case Boolean = 'boolean';
    case ArrayType = 'array';
    case Table = 'table';
    case Image = 'image';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Teks',
            self::Number => 'Angka',
            self::Currency => 'Mata Uang',
            self::Date => 'Tanggal',
            self::Boolean => 'Ya/Tidak',
            self::ArrayType => 'Daftar (Array)',
            self::Table => 'Tabel',
            self::Image => 'Gambar',
        };
    }
}
