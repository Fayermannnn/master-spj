<?php

declare(strict_types=1);

namespace App\Domain\DocumentGenerator\Services;

use App\Models\Deliverable;
use App\Models\Payment;
use App\Models\PersonnelAssignment;
use App\Models\Project;
use Illuminate\Support\Carbon;

/**
 * @domain DocumentGenerator
 *
 * Menerjemahkan `template_variables.key` menjadi nilai nyata dari
 * Project/Contract/Client/Personnel/Payment. Kosakata TERTUTUP (closed
 * `match()`, bukan dot-path bebas lewat reflection) — pola yang sama
 * dengan RequirementRuleEvaluator (PROJECT_DECISIONS.md D-015): variable
 * baru yang ditambah admin lewat UI TemplateVariable tetap bisa dipakai
 * di form generate (diisi manual), tapi auto-resolve untuk variable itu
 * baru aktif setelah ditambahkan satu cabang `match` di sini — lihat
 * D-018.
 */
class VariableResolver
{
    /**
     * @return list<string>
     */
    public function resolvableScalarKeys(): array
    {
        return [
            'project.name', 'project.contract_number', 'project.contract_date',
            'project.contract_value', 'project.start_date', 'project.end_date',
            'client.name', 'client.address', 'ppk.name',
            'provider.name', 'provider.address',
            'payment.amount', 'payment.amount_terbilang', 'payment.termin', 'payment.date',
            'payment.name', 'payment.percentage', 'payment.trigger',
            'deliverable.name', 'deliverable.target_date',
            'document.number',
            'today',
        ];
    }

    /**
     * Placeholder yang TIDAK PERNAH diisi manual/ditampilkan sebagai
     * input teks bebas di form generate — nilainya di-inject otomatis
     * oleh `DocumentGeneratorService` sendiri saat generate (`{{
     * document.number }}` dari `NumberingService`, lihat
     * PROJECT_DECISIONS.md D-029). Beda dari `payment.*`/
     * `deliverable.*` yang auto-fill sebagai DEFAULT tapi tetap bisa
     * diedit manual — nomor surat resmi TIDAK BOLEH bisa diketik bebas
     * user.
     *
     * @return list<string>
     */
    public function reservedKeys(): array
    {
        return ['document.number', 'organization.logo'];
    }

    /**
     * Peta grup tabel -> variable anggotanya. Satu baris tabel di
     * template diulang sekali per baris data (§21 master prompt),
     * lewat `TemplateProcessor::cloneRowAndSetValues()`.
     *
     * @return array<string, list<string>>
     */
    public function tableGroups(): array
    {
        return [
            'personnel' => ['personnel.name', 'personnel.position', 'personnel.npwp'],
        ];
    }

    public function resolveScalar(string $key, Project $project, ?Payment $payment, ?Deliverable $deliverable = null): ?string
    {
        return match ($key) {
            'project.name' => $project->name,
            'project.contract_number' => $project->contract?->contract_number,
            'project.contract_date' => $this->formatDate($project->contract?->contract_date),
            'project.contract_value' => $this->formatCurrency($project->contract?->contract_value),
            'project.start_date' => $this->formatDate($project->start_date),
            'project.end_date' => $this->formatDate($project->end_date),
            'client.name' => $project->client?->name,
            'client.address' => $project->client?->address,
            'ppk.name' => $project->ppkContact?->name,
            'provider.name' => $project->organization?->name,
            'provider.address' => $project->organization?->address,
            'payment.amount' => $this->formatCurrency($payment?->amount),
            'payment.amount_terbilang' => $payment !== null ? $this->terbilangRupiah((float) $payment->amount) : null,
            'payment.termin' => $payment !== null ? (string) $payment->termin_number : null,
            'payment.date' => $payment !== null ? $this->formatDate($payment->payment_date ?? $payment->target_date) : null,
            'payment.name' => $payment?->name,
            'payment.percentage' => $payment?->percentage !== null ? rtrim(rtrim((string) $payment->percentage, '0'), '.').'%' : null,
            'payment.trigger' => $payment?->trigger,
            'deliverable.name' => $deliverable?->name,
            'deliverable.target_date' => $this->formatDate($deliverable?->target_date),
            'today' => $this->formatDate(Carbon::now()),
            default => null,
        };
    }

    /**
     * @return list<array<string, string>>
     */
    public function resolveTableRows(string $group, Project $project): array
    {
        return match ($group) {
            'personnel' => array_values($project->personnelAssignments->map(
                $this->personnelRow(...),
            )->all()),
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    private function personnelRow(PersonnelAssignment $assignment): array
    {
        $personnel = $assignment->personnel;

        return [
            'personnel.name' => $personnel !== null ? $personnel->name : '-',
            'personnel.position' => $assignment->role_on_project
                ?? ($personnel !== null ? $personnel->position : null)
                ?? '-',
            'personnel.npwp' => ($personnel !== null ? $personnel->npwp : null) ?? '-',
        ];
    }

    /**
     * "Terbilang" — nominal dieja jadi kalimat Bahasa Indonesia (mis.
     * "Seratus Juta Rupiah"), lazim di surat keuangan formal (SPP,
     * Kwitansi, Invoice). Rekursif standar, dibulatkan ke rupiah penuh
     * (tidak menangani sen).
     */
    private function terbilangRupiah(float $amount): string
    {
        $rounded = (int) round($amount);

        if ($rounded === 0) {
            return 'Nol Rupiah';
        }

        $words = ucwords(trim(preg_replace('/\s+/', ' ', $this->terbilang(abs($rounded))) ?? ''));

        return ($rounded < 0 ? 'Minus ' : '').$words.' Rupiah';
    }

    private function terbilang(int $number): string
    {
        $ones = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        return match (true) {
            $number < 12 => $ones[$number],
            $number < 20 => $this->terbilang($number - 10).' belas',
            $number < 100 => trim($this->terbilang(intdiv($number, 10)).' puluh '.($number % 10 !== 0 ? $this->terbilang($number % 10) : '')),
            $number < 200 => trim('seratus '.($number % 100 !== 0 ? $this->terbilang($number % 100) : '')),
            $number < 1000 => trim($this->terbilang(intdiv($number, 100)).' ratus '.($number % 100 !== 0 ? $this->terbilang($number % 100) : '')),
            $number < 2000 => trim('seribu '.($number % 1000 !== 0 ? $this->terbilang($number % 1000) : '')),
            $number < 1_000_000 => trim($this->terbilang(intdiv($number, 1000)).' ribu '.($number % 1000 !== 0 ? $this->terbilang($number % 1000) : '')),
            $number < 1_000_000_000 => trim($this->terbilang(intdiv($number, 1_000_000)).' juta '.($number % 1_000_000 !== 0 ? $this->terbilang($number % 1_000_000) : '')),
            $number < 1_000_000_000_000 => trim($this->terbilang(intdiv($number, 1_000_000_000)).' miliar '.($number % 1_000_000_000 !== 0 ? $this->terbilang($number % 1_000_000_000) : '')),
            default => trim($this->terbilang(intdiv($number, 1_000_000_000_000)).' triliun '.($number % 1_000_000_000_000 !== 0 ? $this->terbilang($number % 1_000_000_000_000) : '')),
        };
    }

    private function formatCurrency(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }

    private function formatDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Carbon::parse($value)->translatedFormat('d F Y');
    }
}
