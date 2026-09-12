<?php

declare(strict_types=1);

namespace App\Domain\Settings\Services;

use App\Models\NumberingSetting;
use App\Models\Organization;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @domain Settings
 *
 * Membentuk nomor surat resmi dari `NumberingSetting` organisasi dan
 * menaikkan counter-nya — TRANSAKSIONAL dengan `lockForUpdate()`
 * supaya dua dokumen yang di-generate nyaris bersamaan tidak pernah
 * mendapat nomor urut yang sama (PROJECT_DECISIONS.md D-029). Kosakata
 * token TERTUTUP (pola sama VariableResolver, D-015/D-018) — bukan
 * template engine bebas.
 */
class NumberingService
{
    /**
     * @var list<string>
     */
    private const array ROMAN_MONTHS = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    /**
     * @return string|null null kalau organisasi belum punya
     *                     `NumberingSetting` (fitur opt-in, bukan wajib).
     */
    public function nextNumber(?Organization $organization): ?string
    {
        if ($organization === null) {
            return null;
        }

        return DB::transaction(function () use ($organization): ?string {
            $setting = NumberingSetting::query()
                ->where('organization_id', $organization->id)
                ->lockForUpdate()
                ->first();

            if ($setting === null) {
                return null;
            }

            $now = Carbon::now();
            $this->applyResetIfNeeded($setting, $now);

            $sequence = $setting->next_sequence;
            $formatted = $this->format($setting->format_template, $sequence, $organization, $now);

            $setting->next_sequence = $sequence + 1;
            $setting->save();

            return $formatted;
        });
    }

    /**
     * Pratinjau nomor BERIKUTNYA tanpa menaikkan counter — dipakai UI
     * pengaturan supaya admin bisa melihat contoh hasil format sebelum
     * benar-benar dipakai generate dokumen sungguhan.
     */
    public function previewNext(NumberingSetting $setting, Organization $organization): string
    {
        $now = Carbon::now();
        $periodKey = $this->periodKey($setting->reset_period, $now);
        $sequence = $periodKey !== null && $setting->last_reset_period_key !== $periodKey
            ? 1
            : $setting->next_sequence;

        return $this->format($setting->format_template, $sequence, $organization, $now);
    }

    private function applyResetIfNeeded(NumberingSetting $setting, Carbon $now): void
    {
        $periodKey = $this->periodKey($setting->reset_period, $now);

        if ($periodKey !== null && $setting->last_reset_period_key !== $periodKey) {
            $setting->next_sequence = 1;
            $setting->last_reset_period_key = $periodKey;
        }
    }

    private function periodKey(string $resetPeriod, Carbon $now): ?string
    {
        return match ($resetPeriod) {
            'yearly' => $now->format('Y'),
            'monthly' => $now->format('Y-m'),
            default => null,
        };
    }

    private function format(string $template, int $sequence, Organization $organization, Carbon $now): string
    {
        $replacements = [
            '{seq}' => str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
            '{org}' => $organization->code,
            '{month}' => $now->format('m'),
            '{month_roman}' => self::ROMAN_MONTHS[$now->month - 1],
            '{year}' => $now->format('Y'),
        ];

        return strtr($template, $replacements);
    }
}
