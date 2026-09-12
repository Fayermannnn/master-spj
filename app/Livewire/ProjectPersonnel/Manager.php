<?php

declare(strict_types=1);

namespace App\Livewire\ProjectPersonnel;

use App\Domain\Personnel\Services\PersonnelAssignmentService;
use App\Models\Personnel;
use App\Models\PersonnelAssignment;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Komponen nested — dirender di dalam tab "Personel" pada Projects\Show.
 * Akses diatur lewat ProjectPolicy (bukan policy terpisah), sama seperti
 * Contracts\Form — lihat PROJECT_DECISIONS.md.
 */
class Manager extends Component
{
    public Project $project;

    public ?PersonnelAssignment $editing = null;

    public string $personnel_id = '';

    public string $role_on_project = '';

    public string $quantity = '1';

    public string $unit = 'OB';

    public string $unit_price = '';

    public string $start_date = '';

    public string $end_date = '';

    public string $notes = '';

    public function mount(Project $project): void
    {
        $this->project = $project;

        $this->authorize('update', $this->project);
    }

    public function updatedPersonnelId(string $value): void
    {
        if ($value === '') {
            return;
        }

        $personnel = Personnel::query()->find($value);

        if ($personnel) {
            $this->role_on_project = $this->role_on_project ?: (string) $personnel->position;
            $this->unit_price = $this->unit_price ?: (string) $personnel->default_rate;
        }
    }

    public function edit(string $assignmentId): void
    {
        $this->authorize('update', $this->project);

        $assignment = $this->project->personnelAssignments()->findOrFail($assignmentId);

        $this->editing = $assignment;
        $this->personnel_id = $assignment->personnel_id;
        $this->role_on_project = (string) $assignment->role_on_project;
        $this->quantity = (string) $assignment->quantity;
        $this->unit = $assignment->unit;
        $this->unit_price = (string) $assignment->unit_price;
        $this->start_date = $assignment->start_date?->toDateString() ?? '';
        $this->end_date = $assignment->end_date?->toDateString() ?? '';
        $this->notes = (string) $assignment->notes;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editing', 'personnel_id', 'role_on_project', 'quantity', 'unit', 'unit_price', 'start_date', 'end_date', 'notes']);
        $this->quantity = '1';
        $this->unit = 'OB';
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'personnel_id' => [
                'required', 'ulid',
                Rule::exists('personnel', 'id')->where('organization_id', $this->project->organization_id),
                Rule::unique('personnel_assignments', 'personnel_id')
                    ->where('project_id', $this->project->id)
                    ->ignore($this->editing?->id),
            ],
            'role_on_project' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:20'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function save(PersonnelAssignmentService $service): void
    {
        $this->authorize('update', $this->project);

        $data = $this->validate();
        $data['start_date'] = $data['start_date'] ?: null;
        $data['end_date'] = $data['end_date'] ?: null;

        if ($this->editing) {
            $service->update($this->editing, $data);
            session()->flash('status', 'Penugasan personel berhasil diperbarui.');
        } else {
            $service->create($this->project, $data);
            session()->flash('status', 'Personel berhasil ditugaskan ke project.');
        }

        $this->cancelEdit();
    }

    public function delete(string $assignmentId, PersonnelAssignmentService $service): void
    {
        $this->authorize('update', $this->project);

        $assignment = $this->project->personnelAssignments()->findOrFail($assignmentId);
        $service->delete($assignment);

        session()->flash('status', 'Penugasan personel berhasil dihapus.');
    }

    /**
     * @return Collection<int, Personnel>
     */
    public function personnelOptions(): Collection
    {
        return Personnel::query()
            ->where('organization_id', $this->project->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.project-personnel.manager', [
            'assignments' => $this->project->personnelAssignments()->with('personnel')->latest()->get(),
            'personnelOptions' => $this->personnelOptions(),
        ]);
    }
}
