<?php

declare(strict_types=1);

namespace App\Domain\DocumentGenerator\Services;

use App\Models\CostItem;
use App\Models\Deliverable;
use App\Models\Payment;
use App\Models\Personnel;
use App\Models\PersonnelAssignment;
use App\Models\Project;
use App\Models\TravelAssignment;
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
            'salary.personnel_name', 'salary.personnel_position', 'salary.personnel_npwp',
            'salary.description', 'salary.quantity', 'salary.unit', 'salary.unit_price',
            'salary.subtotal', 'salary.tax_amount', 'salary.total', 'salary.amount_terbilang',
            'salary.period_start', 'salary.period_end',
            'travel.personnel_name', 'travel.personnel_position', 'travel.destination',
            'travel.purpose', 'travel.departure_date', 'travel.return_date', 'travel.transportation_mode',
            'attendance.personnel_name', 'attendance.month_name',
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
            'cost_items' => [
                'cost_item.description', 'cost_item.quantity', 'cost_item.unit',
                'cost_item.unit_price', 'cost_item.subtotal', 'cost_item.tax_amount', 'cost_item.total',
            ],
            'attendance' => ['attendance.date', 'attendance.day_name', 'attendance.signature'],
        ];
    }

    public function resolveScalar(
        string $key,
        Project $project,
        ?Payment $payment,
        ?Deliverable $deliverable = null,
        ?CostItem $costItem = null,
        ?TravelAssignment $travelAssignment = null,
        ?Personnel $attendancePersonnel = null,
        ?string $attendanceMonth = null,
    ): ?string {
        $personnelAssignment = $costItem?->personnelAssignment;
        $personnel = $personnelAssignment?->personnel;
        $travelPersonnel = $travelAssignment?->personnel;

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
            'salary.personnel_name' => $personnel?->name,
            'salary.personnel_position' => $personnelAssignment !== null && $personnelAssignment->role_on_project !== null
                ? $personnelAssignment->role_on_project
                : $personnel?->position,
            'salary.personnel_npwp' => $personnel?->npwp,
            'salary.description' => $costItem?->description,
            'salary.quantity' => $costItem !== null ? rtrim(rtrim((string) $costItem->quantity, '0'), '.') : null,
            'salary.unit' => $costItem?->unit,
            'salary.unit_price' => $this->formatCurrency($costItem?->unit_price),
            'salary.subtotal' => $this->formatCurrency($costItem?->subtotal),
            'salary.tax_amount' => $this->formatCurrency($costItem?->tax_amount),
            'salary.total' => $this->formatCurrency($costItem?->total),
            'salary.amount_terbilang' => $costItem !== null ? $this->terbilangRupiah((float) $costItem->total) : null,
            'salary.period_start' => $this->formatDate($personnelAssignment?->start_date),
            'salary.period_end' => $this->formatDate($personnelAssignment?->end_date),
            'travel.personnel_name' => $travelPersonnel?->name,
            'travel.personnel_position' => $travelPersonnel?->position,
            'travel.destination' => $travelAssignment?->destination,
            'travel.purpose' => $travelAssignment?->purpose,
            'travel.departure_date' => $this->formatDate($travelAssignment?->departure_date),
            'travel.return_date' => $this->formatDate($travelAssignment?->return_date),
            'travel.transportation_mode' => $travelAssignment?->transportation_mode,
            'attendance.personnel_name' => $attendancePersonnel?->name,
            'attendance.month_name' => $attendanceMonth !== null ? $this->formatMonth($attendanceMonth) : null,
            'today' => $this->formatDate(Carbon::now()),
            default => null,
        };
    }

    /**
     * @return list<array<string, string>>
     */
    public function resolveTableRows(string $group, Project $project, ?string $attendanceMonth = null): array
    {
        return match ($group) {
            'personnel' => array_values($project->personnelAssignments->map(
                $this->personnelRow(...),
            )->all()),
            'cost_items' => array_values($project->costItems->map(
                $this->costItemRow(...),
            )->all()),
            'attendance' => $attendanceMonth !== null ? $this->attendanceRows($attendanceMonth) : [],
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
     * @return array<string, string>
     */
    private function costItemRow(CostItem $item): array
    {
        return [
            'cost_item.description' => $item->description,
            'cost_item.quantity' => rtrim(rtrim((string) $item->quantity, '0'), '.'),
            'cost_item.unit' => $item->unit,
            'cost_item.unit_price' => $this->formatCurrency($item->unit_price) ?? '-',
            'cost_item.subtotal' => $this->formatCurrency($item->subtotal) ?? '-',
            'cost_item.tax_amount' => $this->formatCurrency($item->tax_amount) ?? '-',
            'cost_item.total' => $this->formatCurrency($item->total) ?? '-',
        ];
    }

    /**
     * Baris tabel absensi — BUKAN dari tabel database manapun, murni
     * dihitung dari rentang tanggal satu bulan (lembar cetak, absensi
     * DIGITAL sengaja tidak dibangun — lihat PROJECT_DECISIONS.md
     * D-035). `attendance.signature` sengaja berupa garis kosong untuk
     * diisi tanda tangan basah di kertas.
     *
     * @return list<array<string, string>>
     */
    private function attendanceRows(string $attendanceMonth): array
    {
        $start = $this->parseAttendanceMonth($attendanceMonth)->startOfMonth();
        $rows = [];

        for ($day = 0; $day < $start->daysInMonth; $day++) {
            $date = $start->copy()->addDays($day);

            $rows[] = [
                'attendance.date' => $date->translatedFormat('d F Y'),
                'attendance.day_name' => $date->translatedFormat('l'),
                'attendance.signature' => '.....................',
            ];
        }

        return $rows;
    }

    private function formatMonth(string $attendanceMonth): string
    {
        return $this->parseAttendanceMonth($attendanceMonth)->translatedFormat('F Y');
    }

    /**
     * Format "Y-m" dari `<input type="month">` HTML — kalau tidak
     * valid (mis. diutak-atik lewat devtools), diam-diam jatuh ke
     * bulan berjalan alih-alih gagal generate seluruh dokumen hanya
     * karena satu field bantu ini rusak.
     */
    private function parseAttendanceMonth(string $attendanceMonth): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', "{$attendanceMonth}-01") ?: Carbon::now();
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
