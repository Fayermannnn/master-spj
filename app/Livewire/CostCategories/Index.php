<?php

declare(strict_types=1);

namespace App\Livewire\CostCategories;

use App\Domain\Cost\Services\CostCategoryService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\CostCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Kategori Biaya'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', CostCategory::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(string $categoryId, CostCategoryService $service): void
    {
        $category = CostCategory::query()->findOrFail($categoryId);
        $this->authorize('delete', $category);

        try {
            $service->delete($category);
            session()->flash('status', "Kategori biaya \"{$category->name}\" berhasil dihapus.");
        } catch (DomainActionException $exception) {
            $this->addError('delete', $exception->getMessage());
        }
    }

    /**
     * @return LengthAwarePaginator<int, CostCategory>
     */
    public function categories(): LengthAwarePaginator
    {
        return CostCategory::query()
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'ilike', "%{$this->search}%")
                    ->orWhere('code', 'ilike', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.cost-categories.index', [
            'categories' => $this->categories(),
        ]);
    }
}
