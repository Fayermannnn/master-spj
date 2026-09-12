<?php

declare(strict_types=1);

namespace App\Livewire\CostCategories;

use App\Domain\Cost\Services\CostCategoryService;
use App\Models\CostCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?CostCategory $category = null;

    public string $code = '';

    public string $name = '';

    public string $description = '';

    public bool $is_active = true;

    public function mount(?CostCategory $costCategory = null): void
    {
        $this->category = $costCategory?->exists ? $costCategory : null;

        $this->authorize($this->category ? 'update' : 'create', $this->category ?? CostCategory::class);

        if ($this->category) {
            $this->code = $this->category->code;
            $this->name = $this->category->name;
            $this->description = (string) $this->category->description;
            $this->is_active = $this->category->is_active;
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
                Rule::unique('cost_categories', 'code')->ignore($this->category?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(CostCategoryService $service): void
    {
        $this->authorize($this->category ? 'update' : 'create', $this->category ?? CostCategory::class);

        $data = $this->validate();

        if ($this->category) {
            $service->update($this->category, $data);
            session()->flash('status', "Kategori biaya \"{$this->name}\" berhasil diperbarui.");
        } else {
            $service->create($data);
            session()->flash('status', "Kategori biaya \"{$this->name}\" berhasil dibuat.");
        }

        $this->redirect(route('cost-categories.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.cost-categories.form')
            ->title($this->category ? 'Ubah Kategori Biaya' : 'Kategori Biaya Baru');
    }
}
