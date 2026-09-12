<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\NumberingSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain Settings
 *
 * Satu baris per organisasi (`organization_id` unique) — konfigurasi
 * format nomor surat resmi + counter berjalan. Lihat
 * `App\Domain\Settings\Services\NumberingService` untuk logika
 * pembentukan nomor & reset periodik (PROJECT_DECISIONS.md D-029).
 */
#[Fillable(['organization_id', 'format_template', 'next_sequence', 'reset_period', 'last_reset_period_key'])]
class NumberingSetting extends Model
{
    /** @use HasFactory<NumberingSettingFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'next_sequence' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
