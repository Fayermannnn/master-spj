<?php

declare(strict_types=1);

namespace App\Livewire\DocumentRequirements;

use App\Domain\DocumentRequirement\Enums\RequirementRuleField;
use App\Domain\DocumentRequirement\Enums\RequirementRuleOperator;
use App\Domain\DocumentRequirement\Services\DocumentRequirementService;
use App\Models\DocumentRequirement;
use App\Models\ProjectType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?DocumentRequirement $requirement = null;

    public string $project_type_id = '';

    public string $code = '';

    public string $name = '';

    public string $category = '';

    public string $description = '';

    public bool $is_active = true;

    public string $sort_order = '0';

    /** @var array<int, array{id: string|null, field: string, operator: string, value: string|null}> */
    public array $rules = [];

    public function mount(?DocumentRequirement $documentRequirement = null): void
    {
        $this->requirement = $documentRequirement?->exists ? $documentRequirement : null;

        $this->authorize($this->requirement ? 'update' : 'create', $this->requirement ?? DocumentRequirement::class);

        if ($this->requirement) {
            $this->project_type_id = (string) $this->requirement->project_type_id;
            $this->code = $this->requirement->code;
            $this->name = $this->requirement->name;
            $this->category = (string) $this->requirement->category;
            $this->description = (string) $this->requirement->description;
            $this->is_active = $this->requirement->is_active;
            $this->sort_order = (string) $this->requirement->sort_order;

            $this->rules = $this->requirement->rules->map(fn ($rule): array => [
                'id' => (string) $rule->id,
                'field' => $rule->field->value,
                'operator' => $rule->operator->value,
                'value' => $rule->value,
            ])->all();
        }
    }

    public function addRule(): void
    {
        $this->rules[] = [
            'id' => null,
            'field' => RequirementRuleField::HasPersonnelAssignments->value,
            'operator' => RequirementRuleOperator::IsTrue->value,
            'value' => null,
        ];
    }

    public function removeRule(int $index): void
    {
        $this->rules = collect($this->rules)
            ->reject(fn (array $rule, int $i): bool => $i === $index)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'project_type_id' => ['nullable', 'ulid', 'exists:project_types,id'],
            'code' => [
                'required', 'string', 'max:50', 'alpha_dash',
                Rule::unique('document_requirements', 'code')->ignore($this->requirement?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'rules' => ['array'],
            'rules.*.field' => ['required', Rule::in(array_map(fn ($case) => $case->value, RequirementRuleField::cases()))],
            'rules.*.operator' => ['required', Rule::in(array_map(fn ($case) => $case->value, RequirementRuleOperator::cases()))],
            'rules.*.value' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return Collection<int, ProjectType>
     */
    public function projectTypeOptions(): Collection
    {
        return ProjectType::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function save(DocumentRequirementService $service): void
    {
        $this->authorize($this->requirement ? 'update' : 'create', $this->requirement ?? DocumentRequirement::class);

        $data = $this->validate();
        $ruleRows = $data['rules'];
        unset($data['rules']);

        $data['project_type_id'] = $data['project_type_id'] ?: null;

        if ($this->requirement) {
            $service->update($this->requirement, $data, $ruleRows);
            session()->flash('status', "Kebutuhan dokumen \"{$this->name}\" berhasil diperbarui.");
        } else {
            $service->create($data, $ruleRows);
            session()->flash('status', "Kebutuhan dokumen \"{$this->name}\" berhasil dibuat.");
        }

        $this->redirect(route('document-requirements.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.document-requirements.form', [
            'projectTypeOptions' => $this->projectTypeOptions(),
            'fieldOptions' => RequirementRuleField::cases(),
            'operatorOptions' => RequirementRuleOperator::cases(),
        ])->title($this->requirement ? 'Ubah Kebutuhan Dokumen' : 'Kebutuhan Dokumen Baru');
    }
}
