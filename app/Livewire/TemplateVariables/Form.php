<?php

declare(strict_types=1);

namespace App\Livewire\TemplateVariables;

use App\Domain\DocumentTemplate\Enums\TemplateVariableDataType;
use App\Domain\DocumentTemplate\Services\TemplateVariableService;
use App\Models\TemplateVariable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?TemplateVariable $variable = null;

    public string $key = '';

    public string $label = '';

    public string $data_type = '';

    public string $description = '';

    public bool $is_active = true;

    public function mount(?TemplateVariable $templateVariable = null): void
    {
        $this->variable = $templateVariable?->exists ? $templateVariable : null;

        $this->authorize($this->variable ? 'update' : 'create', $this->variable ?? TemplateVariable::class);

        if ($this->variable) {
            $this->key = $this->variable->key;
            $this->label = $this->variable->label;
            $this->data_type = $this->variable->data_type->value;
            $this->description = (string) $this->variable->description;
            $this->is_active = $this->variable->is_active;
        } else {
            $this->data_type = TemplateVariableDataType::Text->value;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'key' => [
                'required', 'string', 'max:100', 'regex:/^[a-z0-9_]+(\.[a-z0-9_]+)*$/',
                Rule::unique('template_variables', 'key')->ignore($this->variable?->id),
            ],
            'label' => ['required', 'string', 'max:255'],
            'data_type' => ['required', Rule::in(array_map(fn ($case) => $case->value, TemplateVariableDataType::cases()))],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(TemplateVariableService $service): void
    {
        $this->authorize($this->variable ? 'update' : 'create', $this->variable ?? TemplateVariable::class);

        $data = $this->validate();

        if ($this->variable) {
            $service->update($this->variable, $data);
            session()->flash('status', "Variabel \"{$this->key}\" berhasil diperbarui.");
        } else {
            $service->create($data);
            session()->flash('status', "Variabel \"{$this->key}\" berhasil dibuat.");
        }

        $this->redirect(route('template-variables.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.template-variables.form', [
            'dataTypeOptions' => TemplateVariableDataType::cases(),
        ])->title($this->variable ? 'Ubah Variabel' : 'Variabel Baru');
    }
}
