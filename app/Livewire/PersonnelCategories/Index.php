<?php

declare(strict_types=1);

namespace App\Livewire\PersonnelCategories;

use App\Domain\Personnel\Services\PersonnelCategoryService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\PersonnelCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Kategori Personel'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', PersonnelCategory::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(string $categoryId, PersonnelCategoryService $service): void
    {
        $category = PersonnelCategory::query()->findOrFail($categoryId);
        $this->authorize('delete', $category);

        try {
            $service->delete($category);
            session()->flash('status', "Kategori personel \"{$category->name}\" berhasil dihapus.");
        } catch (DomainActionException $exception) {
            $this->addError('delete', $exception->getMessage());
        }
    }

    /**
     * @return LengthAwarePaginator<int, PersonnelCategory>
     */
    public function categories(): LengthAwarePaginator
    {
        return PersonnelCategory::query()
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'ilike', "%{$this->search}%")
                    ->orWhere('code', 'ilike', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.personnel-categories.index', [
            'categories' => $this->categories(),
        ]);
    }
}
