<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Domain\ProjectManagement\Enums\ProjectStatus;
use App\Domain\ProjectManagement\Services\ProjectService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Project $project;

    public string $activeTab = 'overview';

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->project = $project->load(['organization', 'projectType', 'client', 'ppkContact', 'contract']);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * @return list<ProjectStatus>
     */
    public function availableTransitions(): array
    {
        $targets = ProjectStatus::allowedTransitions()[$this->project->status->value];

        return array_map(fn (string $value) => ProjectStatus::from($value), $targets);
    }

    public function transitionTo(string $status, ProjectService $service): void
    {
        $this->authorize('transitionStatus', $this->project);

        try {
            $service->transitionStatus($this->project, ProjectStatus::from($status));
            $this->project->refresh();
            session()->flash('status', "Status project diubah menjadi \"{$this->project->status->label()}\".");
        } catch (DomainActionException $exception) {
            $this->addError('status', $exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.projects.show')->title($this->project->name);
    }
}
