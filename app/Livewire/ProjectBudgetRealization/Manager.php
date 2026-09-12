<?php

declare(strict_types=1);

namespace App\Livewire\ProjectBudgetRealization;

use App\Domain\Cost\Services\BudgetRealizationService;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Komponen nested — dirender di dalam tab "Realisasi Anggaran" pada
 * Projects\Show. Read-only (hanya butuh `view`, bukan `update`, karena
 * tidak ada aksi menulis di sini — alokasi ditulis lewat
 * ProjectPayments\Manager).
 */
class Manager extends Component
{
    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;

        $this->authorize('view', $this->project);
    }

    public function render(BudgetRealizationService $service): View
    {
        return view('livewire.project-budget-realization.manager', [
            'rows' => $service->rows($this->project),
            'totals' => $service->totals($this->project),
        ]);
    }
}
