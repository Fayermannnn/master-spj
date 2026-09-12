<?php

declare(strict_types=1);

namespace App\Livewire\TemplateVariables;

use App\Domain\DocumentTemplate\Services\TemplateVariableService;
use App\Models\TemplateVariable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Variabel Template'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', TemplateVariable::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(string $variableId, TemplateVariableService $service): void
    {
        $variable = TemplateVariable::query()->findOrFail($variableId);
        $this->authorize('delete', $variable);

        $service->delete($variable);
        session()->flash('status', "Variabel \"{$variable->key}\" berhasil dihapus.");
    }

    /**
     * @return LengthAwarePaginator<int, TemplateVariable>
     */
    public function variables(): LengthAwarePaginator
    {
        return TemplateVariable::query()
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('key', 'ilike', "%{$this->search}%")
                    ->orWhere('label', 'ilike', "%{$this->search}%");
            }))
            ->orderBy('key')
            ->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.template-variables.index', [
            'variables' => $this->variables(),
        ]);
    }
}
