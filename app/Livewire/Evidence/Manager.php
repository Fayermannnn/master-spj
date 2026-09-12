<?php

declare(strict_types=1);

namespace App\Livewire\Evidence;

use App\Domain\DocumentRequirement\Services\ChecklistService;
use App\Domain\Evidence\Services\EvidenceService;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Komponen nested — dirender di dalam tab "Bukti Pendukung" pada
 * Projects\Show. Akses diatur lewat ProjectPolicy, sama seperti manager
 * lain di project (D-014/D-018).
 */
class Manager extends Component
{
    use WithFileUploads;

    public Project $project;

    #[Validate('required|file|max:10240')]
    public mixed $file = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public string $category = '';

    public string $description = '';

    public string $payment_id = '';

    public string $personnel_id = '';

    public string $document_requirement_id = '';

    public string $notes = '';

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->project = $project;
    }

    public function upload(EvidenceService $service): void
    {
        $this->authorize('update', $this->project);

        $this->validate([
            'file' => 'required|file|max:10240',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        /** @var User $viewer */
        $viewer = Auth::user();

        $service->upload($this->project, $this->file, $this->name, [
            'payment_id' => $this->payment_id ?: null,
            'personnel_id' => $this->personnel_id ?: null,
            'document_requirement_id' => $this->document_requirement_id ?: null,
            'category' => $this->category ?: null,
            'description' => $this->description ?: null,
            'notes' => $this->notes ?: null,
        ], $viewer);

        $this->reset(['file', 'name', 'category', 'description', 'payment_id', 'personnel_id', 'document_requirement_id', 'notes']);
        session()->flash('status', 'Bukti pendukung berhasil diunggah.');
    }

    public function deleteEvidence(string $evidenceId, EvidenceService $service): void
    {
        $this->authorize('update', $this->project);

        $evidence = $this->project->evidences()->findOrFail($evidenceId);
        $service->delete($evidence);

        session()->flash('status', 'Bukti pendukung berhasil dihapus.');
    }

    public function render(ChecklistService $checklistService): View
    {
        return view('livewire.evidence.manager', [
            'evidences' => $this->project->evidences()
                ->with(['payment', 'personnel', 'documentRequirement', 'uploadedBy'])
                ->latest()
                ->get(),
            'payments' => $this->project->payments,
            'personnelOptions' => $this->project->personnelAssignments()->with('personnel')->get()->pluck('personnel')->filter()->unique('id'),
            'requirements' => $checklistService->applicableRequirements($this->project),
        ]);
    }
}
