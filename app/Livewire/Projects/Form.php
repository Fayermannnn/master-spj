<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Domain\Identity\Enums\RoleName;
use App\Domain\ProjectManagement\Services\ProjectService;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Project $project = null;

    public ?string $organization_id = null;

    public string $project_type_id = '';

    public string $client_id = '';

    public ?string $ppk_contact_id = null;

    public string $code = '';

    public string $name = '';

    public string $unit_work = '';

    public string $project_manager_name = '';

    public string $start_date = '';

    public string $end_date = '';

    public string $description = '';

    public string $notes = '';

    public function mount(?Project $project = null): void
    {
        $this->project = $project?->exists ? $project : null;

        $this->authorize($this->project ? 'update' : 'create', $this->project ?? Project::class);

        /** @var User $viewer */
        $viewer = Auth::user();

        if ($this->project) {
            $this->organization_id = $this->project->organization_id;
            $this->project_type_id = $this->project->project_type_id;
            $this->client_id = $this->project->client_id;
            $this->ppk_contact_id = $this->project->ppk_contact_id;
            $this->code = $this->project->code;
            $this->name = $this->project->name;
            $this->unit_work = (string) $this->project->unit_work;
            $this->project_manager_name = (string) $this->project->project_manager_name;
            $this->start_date = $this->project->start_date?->toDateString() ?? '';
            $this->end_date = $this->project->end_date?->toDateString() ?? '';
            $this->description = (string) $this->project->description;
            $this->notes = (string) $this->project->notes;
        } elseif (! $viewer->hasRole(RoleName::SuperAdmin->value)) {
            $this->organization_id = $viewer->organization_id;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'organization_id' => ['required', 'ulid', 'exists:organizations,id'],
            'project_type_id' => ['required', 'ulid', 'exists:project_types,id'],
            'client_id' => ['required', 'ulid', Rule::exists('clients', 'id')->where('organization_id', $this->organization_id)],
            'ppk_contact_id' => ['nullable', 'ulid', Rule::exists('contacts', 'id')->where('client_id', $this->client_id)],
            'code' => [
                'required', 'string', 'max:50', 'alpha_dash',
                Rule::unique('projects', 'code')->ignore($this->project?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'unit_work' => ['nullable', 'string', 'max:255'],
            'project_manager_name' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return Collection<int, Organization>
     */
    public function organizationOptions(): Collection
    {
        return Organization::query()->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * @return Collection<int, ProjectType>
     */
    public function projectTypeOptions(): Collection
    {
        return ProjectType::query()->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Client>
     */
    public function clientOptions(): Collection
    {
        if (! $this->organization_id) {
            return collect();
        }

        return Client::query()
            ->where('organization_id', $this->organization_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Contact>
     */
    public function contactOptions(): Collection
    {
        if (! $this->client_id) {
            return collect();
        }

        return Contact::query()->where('client_id', $this->client_id)->orderBy('name')->get();
    }

    public function updatedClientId(): void
    {
        $this->ppk_contact_id = null;
    }

    public function save(ProjectService $service): void
    {
        $this->authorize($this->project ? 'update' : 'create', $this->project ?? Project::class);

        /** @var User $viewer */
        $viewer = Auth::user();

        // Tentukan organization_id yang benar SEBELUM validasi (bukan sesudah):
        // client_id/ppk_contact_id divalidasi relatif terhadap organization_id
        // ini, jadi mengoreksinya setelah validate() bisa membiarkan project
        // tersimpan dengan client dari organisasi lain (bug tamper turunan).
        if (! $viewer->hasRole(RoleName::SuperAdmin->value)) {
            $this->organization_id = $viewer->organization_id;
        }

        $data = $this->validate();

        $data['start_date'] = $data['start_date'] ?: null;
        $data['end_date'] = $data['end_date'] ?: null;
        $data['duration_days'] = ($data['start_date'] && $data['end_date'])
            ? Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date']))
            : null;

        if ($this->project) {
            $service->update($this->project, $data);
            session()->flash('status', "Project \"{$this->name}\" berhasil diperbarui.");
            $this->redirect(route('projects.show', $this->project), navigate: true);

            return;
        }

        $data['created_by'] = $viewer->id;
        $project = $service->create($data);
        session()->flash('status', "Project \"{$this->name}\" berhasil dibuat.");
        $this->redirect(route('projects.show', $project), navigate: true);
    }

    public function render(): View
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return view('livewire.projects.form', [
            'organizationOptions' => $this->organizationOptions(),
            'projectTypeOptions' => $this->projectTypeOptions(),
            'clientOptions' => $this->clientOptions(),
            'contactOptions' => $this->contactOptions(),
            'canChooseOrganization' => $viewer->hasRole(RoleName::SuperAdmin->value),
        ])->title($this->project ? 'Ubah Project' : 'Project Baru');
    }
}
