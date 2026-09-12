<?php

declare(strict_types=1);

namespace App\Livewire\Workplan;

use App\Domain\Workplan\Enums\WorkplanStatus;
use App\Domain\Workplan\Services\DeliverableService;
use App\Domain\Workplan\Services\MilestoneService;
use App\Models\Deliverable;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Komponen nested — dirender di dalam tab "Timeline" pada Projects\Show.
 * Mengelola Milestone dan Deliverable sekaligus (domain Workplan). Akses
 * diatur lewat ProjectPolicy, sama seperti manager lain di project
 * (D-014/D-018/D-019) — tidak ada permission baru.
 */
class Manager extends Component
{
    public Project $project;

    public ?string $editingMilestoneId = null;

    public string $milestoneName = '';

    public string $milestoneDescription = '';

    public string $milestoneTargetDate = '';

    public string $milestoneNotes = '';

    public ?string $editingDeliverableId = null;

    public string $deliverableName = '';

    public string $deliverableDescription = '';

    public string $deliverableTargetDate = '';

    public string $deliverableMilestoneId = '';

    public string $deliverableNotes = '';

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->project = $project;
    }

    public function editMilestone(string $milestoneId): void
    {
        $this->authorize('update', $this->project);

        $milestone = $this->project->milestones()->findOrFail($milestoneId);

        $this->editingMilestoneId = $milestone->id;
        $this->milestoneName = $milestone->name;
        $this->milestoneDescription = (string) $milestone->description;
        $this->milestoneTargetDate = $milestone->target_date->toDateString();
        $this->milestoneNotes = (string) $milestone->notes;
    }

    public function cancelMilestoneEdit(): void
    {
        $this->reset(['editingMilestoneId', 'milestoneName', 'milestoneDescription', 'milestoneTargetDate', 'milestoneNotes']);
    }

    public function saveMilestone(MilestoneService $service): void
    {
        $this->authorize('update', $this->project);

        $this->validate([
            'milestoneName' => 'required|string|max:255',
            'milestoneDescription' => 'nullable|string|max:1000',
            'milestoneTargetDate' => 'required|date',
            'milestoneNotes' => 'nullable|string|max:1000',
        ]);

        $data = [
            'name' => $this->milestoneName,
            'description' => $this->milestoneDescription ?: null,
            'target_date' => $this->milestoneTargetDate,
            'notes' => $this->milestoneNotes ?: null,
        ];

        if ($this->editingMilestoneId !== null) {
            $milestone = $this->project->milestones()->findOrFail($this->editingMilestoneId);
            $service->update($milestone, $data);
        } else {
            /** @var User $creator */
            $creator = Auth::user();
            $service->create($this->project, $data, $creator);
        }

        $this->cancelMilestoneEdit();
        session()->flash('status', 'Milestone berhasil disimpan.');
    }

    public function setMilestoneStatus(string $milestoneId, string $status, MilestoneService $service): void
    {
        $this->authorize('update', $this->project);

        $milestone = $this->project->milestones()->findOrFail($milestoneId);
        $data = ['status' => $status];

        if ($status === WorkplanStatus::Completed->value) {
            $data['actual_date'] = Carbon::today()->toDateString();
        }

        $service->update($milestone, $data);
        session()->flash('status', 'Status milestone diperbarui.');
    }

    public function deleteMilestone(string $milestoneId, MilestoneService $service): void
    {
        $this->authorize('update', $this->project);

        $milestone = $this->project->milestones()->findOrFail($milestoneId);
        $service->delete($milestone);

        session()->flash('status', 'Milestone berhasil dihapus.');
    }

    public function editDeliverable(string $deliverableId): void
    {
        $this->authorize('update', $this->project);

        $deliverable = $this->project->deliverables()->findOrFail($deliverableId);

        $this->editingDeliverableId = $deliverable->id;
        $this->deliverableName = $deliverable->name;
        $this->deliverableDescription = (string) $deliverable->description;
        $this->deliverableTargetDate = $deliverable->target_date?->toDateString() ?? '';
        $this->deliverableMilestoneId = (string) $deliverable->milestone_id;
        $this->deliverableNotes = (string) $deliverable->notes;
    }

    public function cancelDeliverableEdit(): void
    {
        $this->reset(['editingDeliverableId', 'deliverableName', 'deliverableDescription', 'deliverableTargetDate', 'deliverableMilestoneId', 'deliverableNotes']);
    }

    public function saveDeliverable(DeliverableService $service): void
    {
        $this->authorize('update', $this->project);

        $this->validate([
            'deliverableName' => 'required|string|max:255',
            'deliverableDescription' => 'nullable|string|max:1000',
            'deliverableTargetDate' => 'nullable|date',
            'deliverableNotes' => 'nullable|string|max:1000',
        ]);

        $data = [
            'name' => $this->deliverableName,
            'description' => $this->deliverableDescription ?: null,
            'target_date' => $this->deliverableTargetDate ?: null,
            'milestone_id' => $this->deliverableMilestoneId ?: null,
            'notes' => $this->deliverableNotes ?: null,
        ];

        if ($this->editingDeliverableId !== null) {
            $deliverable = $this->project->deliverables()->findOrFail($this->editingDeliverableId);
            $service->update($deliverable, $data);
        } else {
            /** @var User $creator */
            $creator = Auth::user();
            $service->create($this->project, $data, $creator);
        }

        $this->cancelDeliverableEdit();
        session()->flash('status', 'Deliverable berhasil disimpan.');
    }

    public function setDeliverableStatus(string $deliverableId, string $status, DeliverableService $service): void
    {
        $this->authorize('update', $this->project);

        $deliverable = $this->project->deliverables()->findOrFail($deliverableId);
        $data = ['status' => $status];

        if ($status === WorkplanStatus::Completed->value) {
            $data['completed_date'] = Carbon::today()->toDateString();
        }

        $service->update($deliverable, $data);
        session()->flash('status', 'Status deliverable diperbarui.');
    }

    public function deleteDeliverable(string $deliverableId, DeliverableService $service): void
    {
        $this->authorize('update', $this->project);

        $deliverable = $this->project->deliverables()->findOrFail($deliverableId);
        $service->delete($deliverable);

        session()->flash('status', 'Deliverable berhasil dihapus.');
    }

    /**
     * Posisi persentase (0-100) sebuah tanggal dalam rentang tanggal
     * mulai/selesai project — dipakai untuk marker garis waktu
     * (Gantt-lite). Mengembalikan null kalau project tidak punya
     * rentang tanggal (start_date/end_date belum diisi).
     */
    public function timelinePosition(Carbon $date): ?float
    {
        $start = $this->project->start_date;
        $end = $this->project->end_date;

        if ($start === null || $end === null || $end->lte($start)) {
            return null;
        }

        $totalDays = $start->diffInDays($end);
        $elapsedDays = $start->diffInDays($date, false);

        $percentage = ($elapsedDays / $totalDays) * 100;

        return (float) max(0, min(100, $percentage));
    }

    public function render(): View
    {
        return view('livewire.workplan.manager', [
            'milestones' => $this->project->milestones()->orderBy('target_date')->get(),
            'deliverables' => $this->project->deliverables()->with('milestone')->orderBy('target_date')->get(),
            'statuses' => WorkplanStatus::cases(),
            'todayPosition' => $this->timelinePosition(Carbon::today()),
        ]);
    }
}
