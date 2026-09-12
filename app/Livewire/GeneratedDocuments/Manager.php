<?php

declare(strict_types=1);

namespace App\Livewire\GeneratedDocuments;

use App\Domain\DocumentGenerator\Services\DocumentGeneratorService;
use App\Domain\DocumentGenerator\Services\VariableResolver;
use App\Domain\DocumentRequirement\Services\ChecklistService;
use App\Domain\DocumentTemplate\Enums\TemplateStatus;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\CostItem;
use App\Models\Deliverable;
use App\Models\Document;
use App\Models\DocumentRequirement;
use App\Models\DocumentTemplate;
use App\Models\Payment;
use App\Models\Personnel;
use App\Models\Project;
use App\Models\TravelAssignment;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Komponen nested — dirender di dalam tab "Dokumen" pada Projects\Show.
 * Akses diatur lewat ProjectPolicy, sama seperti manager lain di project
 * (Payment/CostItem/Checklist) — lihat PROJECT_DECISIONS.md D-014/D-018.
 */
class Manager extends Component
{
    public Project $project;

    public ?string $selectedRequirementId = null;

    /**
     * List sejajar dengan `tablelessDetectedKeys()` (indeks ke-i adalah
     * nilai untuk key ke-i) — BUKAN dikunci oleh nama placeholder itu
     * sendiri, karena `wire:model` Livewire mengartikan setiap titik
     * pada path sebagai array bersarang. Placeholder seperti
     * "project.name" mengandung titik literal, jadi
     * `variableInputs.project.name` akan salah diartikan sebagai
     * `variableInputs['project']['name']`, bukan
     * `variableInputs['project.name']` — lihat PROJECT_DECISIONS.md
     * D-018.
     *
     * @var array<int, string>
     */
    public array $variableInputs = [];

    public ?string $selectedPaymentId = null;

    public ?string $selectedDeliverableId = null;

    public ?string $selectedCostItemId = null;

    public ?string $selectedTravelAssignmentId = null;

    public ?string $selectedAttendancePersonnelId = null;

    /**
     * Format "Y-m" (mis. "2026-03") — BUKAN memilih record yang sudah
     * ada, absensi cetak dihitung on-the-fly dari rentang tanggal satu
     * bulan (PROJECT_DECISIONS.md D-035), bukan tabel database.
     */
    public string $attendanceMonth = '';

    /**
     * Cache per-render (BUKAN state Livewire — `private`, tidak
     * disinkronkan lewat wire) supaya tab "Dokumen" tidak menjalankan
     * 2 query tambahan PER checklist item (N+1 nyata yang ditemukan
     * saat QA Phase 10 — `activeTemplateFor()`/`generatedDocumentsFor()`
     * dipanggil sekali per baris di blade). `render()` mengisi kedua
     * peta ini SEKALI dengan `whereIn`, method di bawah membaca dari
     * cache ini kalau sudah terisi.
     *
     * @var array<string, DocumentTemplate>|null
     */
    private ?array $activeTemplatesByRequirement = null;

    /**
     * @var array<string, Collection<int, Document>>|null
     */
    private ?array $documentsByRequirement = null;

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->project = $project;
    }

    public function openGenerateForm(string $requirementId, VariableResolver $resolver): void
    {
        $this->authorize('update', $this->project);

        $this->selectedRequirementId = $requirementId;
        $this->selectedPaymentId = null;
        $this->selectedDeliverableId = null;
        $this->selectedCostItemId = null;
        $this->selectedTravelAssignmentId = null;
        $this->selectedAttendancePersonnelId = null;
        $this->attendanceMonth = '';

        $template = $this->activeTemplateFor($requirementId);
        $this->variableInputs = $this->prefillInputs($template, $resolver, null, null, null, null, null, null);
    }

    public function updatedSelectedPaymentId(): void
    {
        if ($this->selectedRequirementId === null) {
            return;
        }

        $resolver = app(VariableResolver::class);
        $template = $this->activeTemplateFor($this->selectedRequirementId);
        $payment = $this->selectedPaymentId !== null && $this->selectedPaymentId !== ''
            ? $this->project->payments()->find($this->selectedPaymentId)
            : null;

        foreach ($this->tablelessDetectedKeys($template, $resolver) as $index => $key) {
            if (str_starts_with($key, 'payment.')) {
                $this->variableInputs[$index] = $resolver->resolveScalar($key, $this->project, $payment) ?? '';
            }
        }
    }

    /**
     * Mengisi otomatis `deliverable.*` sebagai DEFAULT saat sebuah
     * Deliverable dipilih — sama seperti `updatedSelectedPaymentId()`.
     * User tetap bisa mengetik ulang secara manual sesudahnya (mis.
     * project tanpa Deliverable yang cocok) — memilih Deliverable
     * bersifat opsional, bukan wajib (PROJECT_DECISIONS.md D-027).
     */
    public function updatedSelectedDeliverableId(): void
    {
        if ($this->selectedRequirementId === null) {
            return;
        }

        $resolver = app(VariableResolver::class);
        $template = $this->activeTemplateFor($this->selectedRequirementId);
        $deliverable = $this->selectedDeliverableId !== null && $this->selectedDeliverableId !== ''
            ? $this->project->deliverables()->find($this->selectedDeliverableId)
            : null;

        foreach ($this->tablelessDetectedKeys($template, $resolver) as $index => $key) {
            if (str_starts_with($key, 'deliverable.')) {
                $this->variableInputs[$index] = $resolver->resolveScalar($key, $this->project, null, $deliverable) ?? '';
            }
        }
    }

    /**
     * Mengisi otomatis `salary.*` sebagai DEFAULT saat sebuah CostItem
     * personil dipilih — sama seperti Payment/Deliverable di atas.
     */
    public function updatedSelectedCostItemId(): void
    {
        if ($this->selectedRequirementId === null) {
            return;
        }

        $resolver = app(VariableResolver::class);
        $template = $this->activeTemplateFor($this->selectedRequirementId);
        $costItem = $this->selectedCostItemId !== null && $this->selectedCostItemId !== ''
            ? $this->project->costItems()->find($this->selectedCostItemId)
            : null;

        foreach ($this->tablelessDetectedKeys($template, $resolver) as $index => $key) {
            if (str_starts_with($key, 'salary.')) {
                $this->variableInputs[$index] = $resolver->resolveScalar($key, $this->project, null, null, $costItem) ?? '';
            }
        }
    }

    /**
     * Mengisi otomatis `travel.*` sebagai DEFAULT saat sebuah
     * TravelAssignment dipilih — sama seperti selector lain di atas.
     */
    public function updatedSelectedTravelAssignmentId(): void
    {
        if ($this->selectedRequirementId === null) {
            return;
        }

        $resolver = app(VariableResolver::class);
        $template = $this->activeTemplateFor($this->selectedRequirementId);
        $travelAssignment = $this->selectedTravelAssignmentId !== null && $this->selectedTravelAssignmentId !== ''
            ? $this->project->travelAssignments()->find($this->selectedTravelAssignmentId)
            : null;

        foreach ($this->tablelessDetectedKeys($template, $resolver) as $index => $key) {
            if (str_starts_with($key, 'travel.')) {
                $this->variableInputs[$index] = $resolver->resolveScalar($key, $this->project, null, null, null, $travelAssignment) ?? '';
            }
        }
    }

    /**
     * Mengisi otomatis `attendance.*` sebagai DEFAULT saat personil
     * dan/atau bulan dipilih — dipanggil dari DUA properti berbeda
     * (personil, bulan) karena keduanya sama-sama mempengaruhi nilai
     * `attendance.personnel_name`/`attendance.month_name`.
     */
    public function updatedSelectedAttendancePersonnelId(): void
    {
        $this->refillAttendanceVariables();
    }

    public function updatedAttendanceMonth(): void
    {
        $this->refillAttendanceVariables();
    }

    private function refillAttendanceVariables(): void
    {
        if ($this->selectedRequirementId === null) {
            return;
        }

        $resolver = app(VariableResolver::class);
        $template = $this->activeTemplateFor($this->selectedRequirementId);
        $personnel = $this->selectedAttendancePersonnelId !== null && $this->selectedAttendancePersonnelId !== ''
            ? Personnel::query()->find($this->selectedAttendancePersonnelId)
            : null;
        $month = $this->attendanceMonth !== '' ? $this->attendanceMonth : null;

        foreach ($this->tablelessDetectedKeys($template, $resolver) as $index => $key) {
            if (str_starts_with($key, 'attendance.')) {
                $this->variableInputs[$index] = $resolver->resolveScalar($key, $this->project, null, null, null, null, $personnel, $month) ?? '';
            }
        }
    }

    public function closeGenerateForm(): void
    {
        $this->reset([
            'selectedRequirementId', 'variableInputs', 'selectedPaymentId', 'selectedDeliverableId',
            'selectedCostItemId', 'selectedTravelAssignmentId', 'selectedAttendancePersonnelId', 'attendanceMonth',
        ]);
    }

    public function generate(DocumentGeneratorService $service): void
    {
        $this->authorize('update', $this->project);

        if ($this->selectedRequirementId === null) {
            return;
        }

        $requirement = DocumentRequirement::query()->findOrFail($this->selectedRequirementId);
        $template = $this->activeTemplateFor($this->selectedRequirementId);

        if ($template === null) {
            $this->addError('generate', 'Tidak ada versi template yang aktif untuk requirement ini.');

            return;
        }

        $payment = $this->selectedPaymentId !== null && $this->selectedPaymentId !== ''
            ? $this->project->payments()->find($this->selectedPaymentId)
            : null;

        $deliverable = $this->selectedDeliverableId !== null && $this->selectedDeliverableId !== ''
            ? $this->project->deliverables()->find($this->selectedDeliverableId)
            : null;

        $costItem = $this->selectedCostItemId !== null && $this->selectedCostItemId !== ''
            ? $this->project->costItems()->find($this->selectedCostItemId)
            : null;

        $travelAssignment = $this->selectedTravelAssignmentId !== null && $this->selectedTravelAssignmentId !== ''
            ? $this->project->travelAssignments()->find($this->selectedTravelAssignmentId)
            : null;

        $attendancePersonnel = $this->selectedAttendancePersonnelId !== null && $this->selectedAttendancePersonnelId !== ''
            ? Personnel::query()->find($this->selectedAttendancePersonnelId)
            : null;
        $attendanceMonth = $this->attendanceMonth !== '' ? $this->attendanceMonth : null;

        /** @var User $user */
        $user = Auth::user();

        $keys = $this->tablelessDetectedKeys($template);
        $scalarValues = array_combine($keys, array_pad($this->variableInputs, count($keys), ''));

        try {
            $service->generate($this->project, $requirement, $template, $scalarValues, $payment, $user, $deliverable, $costItem, $travelAssignment, $attendancePersonnel, $attendanceMonth);
            $this->closeGenerateForm();
            session()->flash('status', "Dokumen \"{$requirement->name}\" berhasil digenerate.");
        } catch (DomainActionException $exception) {
            $this->addError('generate', $exception->getMessage());
        }
    }

    public function delete(string $documentId, DocumentGeneratorService $service): void
    {
        $this->authorize('update', $this->project);

        $document = $this->project->documents()->findOrFail($documentId);
        $service->delete($document);

        session()->flash('status', 'Dokumen berhasil dihapus.');
    }

    public function activeTemplateFor(string $requirementId): ?DocumentTemplate
    {
        if ($this->activeTemplatesByRequirement !== null) {
            return $this->activeTemplatesByRequirement[$requirementId] ?? null;
        }

        return DocumentTemplate::query()
            ->where('document_requirement_id', $requirementId)
            ->where('status', TemplateStatus::Active->value)
            ->first();
    }

    /**
     * @return Collection<int, Document>
     */
    public function generatedDocumentsFor(string $requirementId): Collection
    {
        if ($this->documentsByRequirement !== null) {
            return $this->documentsByRequirement[$requirementId] ?? collect();
        }

        return $this->project->documents()
            ->where('document_requirement_id', $requirementId)
            ->orderByDesc('version')
            ->with('generatedBy')
            ->get();
    }

    public function needsPaymentContext(?DocumentTemplate $template): bool
    {
        return collect($this->tablelessDetectedKeys($template))
            ->contains(fn (string $key): bool => str_starts_with($key, 'payment.'));
    }

    public function needsDeliverableContext(?DocumentTemplate $template): bool
    {
        return collect($this->tablelessDetectedKeys($template))
            ->contains(fn (string $key): bool => str_starts_with($key, 'deliverable.'));
    }

    public function needsSalaryContext(?DocumentTemplate $template): bool
    {
        return collect($this->tablelessDetectedKeys($template))
            ->contains(fn (string $key): bool => str_starts_with($key, 'salary.'));
    }

    /**
     * Item biaya yang TERHUBUNG ke penugasan personil — Slip Gaji
     * hanya masuk akal untuk baris yang mewakili honor/fee seseorang,
     * bukan biaya non-personil (mis. sewa kendaraan).
     *
     * @return Collection<int, CostItem>
     */
    public function salaryCostItemOptions(): Collection
    {
        return $this->project->costItems()
            ->whereNotNull('personnel_assignment_id')
            ->with('personnelAssignment.personnel')
            ->get();
    }

    public function needsTravelContext(?DocumentTemplate $template): bool
    {
        return collect($this->tablelessDetectedKeys($template))
            ->contains(fn (string $key): bool => str_starts_with($key, 'travel.'));
    }

    /**
     * @return Collection<int, TravelAssignment>
     */
    public function travelAssignmentOptions(): Collection
    {
        return $this->project->travelAssignments()->with('personnel')->latest('departure_date')->get();
    }

    public function needsAttendanceContext(?DocumentTemplate $template): bool
    {
        return collect($this->tablelessDetectedKeys($template))
            ->contains(fn (string $key): bool => str_starts_with($key, 'attendance.'));
    }

    /**
     * Personil yang ditugaskan ke project ini — pilihan absensi
     * dibatasi ke personil yang memang bekerja di project, bukan
     * seluruh personil organisasi.
     *
     * @return Collection<int, Personnel>
     */
    public function attendancePersonnelOptions(): Collection
    {
        return Personnel::query()
            ->whereHas('assignments', fn ($query) => $query->where('project_id', $this->project->id))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<string>
     */
    public function tablelessDetectedKeys(?DocumentTemplate $template, ?VariableResolver $resolver = null): array
    {
        if ($template === null) {
            return [];
        }

        $resolver ??= app(VariableResolver::class);
        $tableKeys = collect($resolver->tableGroups())->flatten()->all();

        /** @var list<string> $detected */
        $detected = array_map(strval(...), $template->detected_variables ?? []);

        return array_values(array_diff($detected, $tableKeys, $resolver->reservedKeys()));
    }

    public function needsDocumentNumber(?DocumentTemplate $template): bool
    {
        if ($template === null) {
            return false;
        }

        return in_array('document.number', $template->detected_variables ?? [], true);
    }

    /**
     * @return list<string>
     */
    private function prefillInputs(?DocumentTemplate $template, VariableResolver $resolver, ?Payment $payment, ?Deliverable $deliverable, ?CostItem $costItem, ?TravelAssignment $travelAssignment, ?Personnel $attendancePersonnel, ?string $attendanceMonth): array
    {
        return array_map(
            fn (string $key): string => $resolver->resolveScalar($key, $this->project, $payment, $deliverable, $costItem, $travelAssignment, $attendancePersonnel, $attendanceMonth) ?? '',
            $this->tablelessDetectedKeys($template, $resolver),
        );
    }

    public function render(ChecklistService $checklistService): View
    {
        $items = $checklistService->sync($this->project);
        $requirementIds = $items->pluck('document_requirement_id');

        $this->activeTemplatesByRequirement = DocumentTemplate::query()
            ->whereIn('document_requirement_id', $requirementIds)
            ->where('status', TemplateStatus::Active->value)
            ->get()
            ->keyBy('document_requirement_id')
            ->all();

        $this->documentsByRequirement = $this->project->documents()
            ->whereIn('document_requirement_id', $requirementIds)
            ->orderByDesc('version')
            ->with('generatedBy')
            ->get()
            ->groupBy('document_requirement_id')
            ->all();

        return view('livewire.generated-documents.manager', [
            'items' => $items,
            'payments' => $this->project->payments,
            'deliverables' => $this->project->deliverables,
        ]);
    }
}
