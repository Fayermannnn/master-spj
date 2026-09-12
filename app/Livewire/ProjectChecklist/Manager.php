<?php

declare(strict_types=1);

namespace App\Livewire\ProjectChecklist;

use App\Domain\DocumentRequirement\Enums\ChecklistStatus;
use App\Domain\DocumentRequirement\Services\ChecklistService;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Komponen nested — dirender di dalam tab "Checklist" pada Projects\Show.
 * Akses diatur lewat ProjectPolicy, sama seperti manager lain di project.
 */
class Manager extends Component
{
    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;

        $this->authorize('view', $this->project);
    }

    public function setStatus(string $checklistItemId, string $status, ChecklistService $service): void
    {
        $this->authorize('update', $this->project);

        $item = $this->project->checklistItems()->findOrFail($checklistItemId);
        $service->updateStatus($item, ChecklistStatus::from($status));

        session()->flash('status', 'Status checklist berhasil diperbarui.');
    }

    public function render(ChecklistService $service): View
    {
        $items = $service->sync($this->project);

        return view('livewire.project-checklist.manager', [
            'items' => $items,
            'total' => $items->count(),
            'fulfilled' => $items->where('status', ChecklistStatus::Fulfilled)->count(),
        ]);
    }
}
