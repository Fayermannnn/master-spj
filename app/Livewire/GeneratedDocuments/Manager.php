<?php

declare(strict_types=1);

namespace App\Livewire\GeneratedDocuments;

use App\Domain\DocumentGenerator\Services\DocumentGeneratorService;
use App\Domain\DocumentGenerator\Services\VariableResolver;
use App\Domain\DocumentRequirement\Services\ChecklistService;
use App\Domain\DocumentTemplate\Enums\TemplateStatus;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\Document;
use App\Models\DocumentRequirement;
use App\Models\DocumentTemplate;
use App\Models\Payment;
use App\Models\Project;
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

        $template = $this->activeTemplateFor($requirementId);
        $this->variableInputs = $this->prefillInputs($template, $resolver, null);
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

    public function closeGenerateForm(): void
    {
        $this->reset(['selectedRequirementId', 'variableInputs', 'selectedPaymentId']);
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

        /** @var User $user */
        $user = Auth::user();

        $keys = $this->tablelessDetectedKeys($template);
        $scalarValues = array_combine($keys, array_pad($this->variableInputs, count($keys), ''));

        try {
            $service->generate($this->project, $requirement, $template, $scalarValues, $payment, $user);
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

        return array_values(array_diff($detected, $tableKeys));
    }

    /**
     * @return list<string>
     */
    private function prefillInputs(?DocumentTemplate $template, VariableResolver $resolver, ?Payment $payment): array
    {
        return array_map(
            fn (string $key): string => $resolver->resolveScalar($key, $this->project, $payment) ?? '',
            $this->tablelessDetectedKeys($template, $resolver),
        );
    }

    public function render(ChecklistService $checklistService): View
    {
        return view('livewire.generated-documents.manager', [
            'items' => $checklistService->sync($this->project),
            'payments' => $this->project->payments,
        ]);
    }
}
