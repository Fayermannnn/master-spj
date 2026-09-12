<?php

declare(strict_types=1);

namespace App\Livewire\ProjectPersonnel;

use App\Domain\Personnel\Services\PersonnelAssignmentService;
use App\Domain\Personnel\Services\TravelAssignmentService;
use App\Models\Personnel;
use App\Models\PersonnelAssignment;
use App\Models\Project;
use App\Models\TravelAssignment;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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

    public bool $showTravelForm = false;

    public ?TravelAssignment $editingTravel = null;

    public string $travel_personnel_id = '';

    public string $travel_destination = '';

    public string $travel_purpose = '';

    public string $travel_departure_date = '';

    public string $travel_return_date = '';

    public string $travel_transportation_mode = '';

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

    public function openTravelForm(): void
    {
        $this->showTravelForm = true;
        $this->editingTravel = null;
        $this->reset(['travel_personnel_id', 'travel_destination', 'travel_purpose', 'travel_departure_date', 'travel_return_date', 'travel_transportation_mode']);
        $this->resetErrorBag();
    }

    public function editTravel(string $travelAssignmentId): void
    {
        $travel = $this->project->travelAssignments()->findOrFail($travelAssignmentId);

        $this->showTravelForm = true;
        $this->editingTravel = $travel;
        $this->travel_personnel_id = $travel->personnel_id;
        $this->travel_destination = $travel->destination;
        $this->travel_purpose = $travel->purpose;
        $this->travel_departure_date = $travel->departure_date->toDateString();
        $this->travel_return_date = $travel->return_date->toDateString();
        $this->travel_transportation_mode = (string) $travel->transportation_mode;
    }

    public function cancelTravelForm(): void
    {
        $this->showTravelForm = false;
        $this->editingTravel = null;
        $this->reset(['travel_personnel_id', 'travel_destination', 'travel_purpose', 'travel_departure_date', 'travel_return_date', 'travel_transportation_mode']);
        $this->resetErrorBag();
    }

    /**
     * @return array<string, mixed>
     */
    protected function travelRules(): array
    {
        return [
            'travel_personnel_id' => [
                'required', 'ulid',
                Rule::exists('personnel', 'id')->where('organization_id', $this->project->organization_id),
            ],
            'travel_destination' => ['required', 'string', 'max:255'],
            'travel_purpose' => ['required', 'string', 'max:1000'],
            'travel_departure_date' => ['required', 'date'],
            'travel_return_date' => ['required', 'date', 'after_or_equal:travel_departure_date'],
            'travel_transportation_mode' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function saveTravel(TravelAssignmentService $service): void
    {
        $this->authorize('update', $this->project);

        $data = $this->validate($this->travelRules());

        $payload = [
            'personnel_id' => $data['travel_personnel_id'],
            'destination' => $data['travel_destination'],
            'purpose' => $data['travel_purpose'],
            'departure_date' => $data['travel_departure_date'],
            'return_date' => $data['travel_return_date'],
            'transportation_mode' => $data['travel_transportation_mode'] ?: null,
        ];

        if ($this->editingTravel) {
            $service->update($this->editingTravel, $payload);
            session()->flash('status', 'Perjalanan dinas berhasil diperbarui.');
        } else {
            /** @var User $creator */
            $creator = Auth::user();
            $service->create($this->project, $payload, $creator);
            session()->flash('status', 'Perjalanan dinas berhasil ditambahkan.');
        }

        $this->cancelTravelForm();
    }

    public function deleteTravel(string $travelAssignmentId, TravelAssignmentService $service): void
    {
        $this->authorize('update', $this->project);

        $travel = $this->project->travelAssignments()->findOrFail($travelAssignmentId);
        $service->delete($travel);

        session()->flash('status', 'Perjalanan dinas berhasil dihapus.');
    }

    public function render(): View
    {
        return view('livewire.project-personnel.manager', [
            'assignments' => $this->project->personnelAssignments()->with('personnel')->latest()->get(),
            'personnelOptions' => $this->personnelOptions(),
            'travelAssignments' => $this->project->travelAssignments()->with('personnel')->latest('departure_date')->get(),
        ]);
    }
}
