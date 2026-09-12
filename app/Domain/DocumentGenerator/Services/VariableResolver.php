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
            'payment.amount', 'payment.termin', 'payment.date',
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
            'payment.termin' => $payment !== null ? (string) $payment->termin_number : null,
            'payment.date' => $payment !== null ? $this->formatDate($payment->payment_date ?? $payment->target_date) : null,
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
