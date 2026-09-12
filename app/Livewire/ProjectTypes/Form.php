<?php

declare(strict_types=1);

namespace App\Livewire\ProjectTypes;

use App\Domain\ProjectManagement\Services\ProjectTypeService;
use App\Models\ProjectType;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?ProjectType $projectType = null;

    public string $code = '';

    public string $name = '';

    public string $description = '';

    public bool $is_active = true;

    public function mount(?ProjectType $projectType = null): void
    {
        $this->projectType = $projectType?->exists ? $projectType : null;

        $this->authorize($this->projectType ? 'update' : 'create', $this->projectType ?? ProjectType::class);

        if ($this->projectType) {
            $this->code = $this->projectType->code;
            $this->name = $this->projectType->name;
            $this->description = (string) $this->projectType->description;
            $this->is_active = $this->projectType->is_active;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:50', 'alpha_dash',
                Rule::unique('project_types', 'code')->ignore($this->projectType?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(ProjectTypeService $service): void
    {
        $this->authorize($this->projectType ? 'update' : 'create', $this->projectType ?? ProjectType::class);

        $data = $this->validate();

        if ($this->projectType) {
            $service->update($this->projectType, $data);
            session()->flash('status', "Jenis project \"{$this->name}\" berhasil diperbarui.");
        } else {
            $service->create($data);
            session()->flash('status', "Jenis project \"{$this->name}\" berhasil dibuat.");
        }

        $this->redirect(route('project-types.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.project-types.form')
            ->title($this->projectType ? 'Ubah Jenis Project' : 'Jenis Project Baru');
    }
}
