<?php

declare(strict_types=1);

namespace App\Livewire\PersonnelCategories;

use App\Domain\Personnel\Services\PersonnelCategoryService;
use App\Models\PersonnelCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?PersonnelCategory $category = null;

    public string $code = '';

    public string $name = '';

    public string $description = '';

    public bool $is_active = true;

    public function mount(?PersonnelCategory $personnelCategory = null): void
    {
        $this->category = $personnelCategory?->exists ? $personnelCategory : null;

        $this->authorize($this->category ? 'update' : 'create', $this->category ?? PersonnelCategory::class);

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
                Rule::unique('personnel_categories', 'code')->ignore($this->category?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(PersonnelCategoryService $service): void
    {
        $this->authorize($this->category ? 'update' : 'create', $this->category ?? PersonnelCategory::class);

        $data = $this->validate();

        if ($this->category) {
            $service->update($this->category, $data);
            session()->flash('status', "Kategori personel \"{$this->name}\" berhasil diperbarui.");
        } else {
            $service->create($data);
            session()->flash('status', "Kategori personel \"{$this->name}\" berhasil dibuat.");
        }

        $this->redirect(route('personnel-categories.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.personnel-categories.form')
            ->title($this->category ? 'Ubah Kategori Personel' : 'Kategori Personel Baru');
    }
}
