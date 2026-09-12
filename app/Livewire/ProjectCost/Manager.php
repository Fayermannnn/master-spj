<?php

declare(strict_types=1);

namespace App\Livewire\ProjectCost;

use App\Domain\Cost\Services\CostItemService;
use App\Models\CostCategory;
use App\Models\CostItem;
use App\Models\Project;
use App\Models\TaxType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Komponen nested — dirender di dalam tab "Biaya" pada Projects\Show.
 * Akses diatur lewat ProjectPolicy (bukan policy terpisah), sama seperti
 * Contracts\Form dan ProjectPersonnel\Manager.
 */
class Manager extends Component
{
    public Project $project;

    public ?CostItem $editing = null;

    public string $cost_category_id = '';

    public string $description = '';

    public string $quantity = '1';

    public string $unit = 'Paket';

    public string $unit_price = '';

    public string $tax_type_id = '';

    public bool $is_tax_inclusive = false;

    public string $notes = '';

    public function mount(Project $project): void
    {
        $this->project = $project;

        $this->authorize('update', $this->project);
    }

    public function edit(string $costItemId): void
    {
        $this->authorize('update', $this->project);

        $item = $this->project->costItems()->findOrFail($costItemId);

        $this->editing = $item;
        $this->cost_category_id = $item->cost_category_id;
        $this->description = $item->description;
        $this->quantity = (string) $item->quantity;
        $this->unit = $item->unit;
        $this->unit_price = (string) $item->unit_price;
        $this->tax_type_id = (string) $item->tax_type_id;
        $this->is_tax_inclusive = $item->is_tax_inclusive;
        $this->notes = (string) $item->notes;
    }

    public function cancelEdit(): void
    {
        $this->reset([
            'editing', 'cost_category_id', 'description', 'quantity', 'unit',
            'unit_price', 'tax_type_id', 'is_tax_inclusive', 'notes',
        ]);
        $this->quantity = '1';
        $this->unit = 'Paket';
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'cost_category_id' => ['required', 'ulid', 'exists:cost_categories,id'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:30'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'tax_type_id' => ['nullable', 'ulid', 'exists:tax_types,id'],
            'is_tax_inclusive' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function save(CostItemService $service): void
    {
        $this->authorize('update', $this->project);

        $data = $this->validate();
        $data['tax_type_id'] = $data['tax_type_id'] ?: null;

        if ($this->editing) {
            $service->update($this->editing, $data);
            session()->flash('status', 'Item biaya berhasil diperbarui.');
        } else {
            $service->create($this->project, $data);
            session()->flash('status', 'Item biaya berhasil ditambahkan.');
        }

        $this->cancelEdit();
    }

    public function delete(string $costItemId, CostItemService $service): void
    {
        $this->authorize('update', $this->project);

        $item = $this->project->costItems()->findOrFail($costItemId);
        $service->delete($item);

        session()->flash('status', 'Item biaya berhasil dihapus.');
    }

    /**
     * @return Collection<int, CostCategory>
     */
    public function categoryOptions(): Collection
    {
        return CostCategory::query()->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * @return Collection<int, TaxType>
     */
    public function taxTypeOptions(): Collection
    {
        return TaxType::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function render(): View
    {
        return view('livewire.project-cost.manager', [
            'costItems' => $this->project->costItems()->with(['category', 'taxType'])->latest()->get(),
            'categoryOptions' => $this->categoryOptions(),
            'taxTypeOptions' => $this->taxTypeOptions(),
        ]);
    }
}
