<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Domain\Identity\Services\UserService;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Pengguna'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(string $userId, UserService $service): void
    {
        $user = User::query()->findOrFail($userId);
        $this->authorize('delete', $user);

        $service->delete($user);
        session()->flash('status', "Pengguna \"{$user->name}\" berhasil dihapus.");
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function users(): LengthAwarePaginator
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return User::query()
            ->with(['organization', 'roles'])
            ->when(! $viewer->hasRole('super_admin'), fn ($query) => $query->where('organization_id', $viewer->organization_id))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'ilike', "%{$this->search}%")
                    ->orWhere('email', 'ilike', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.users.index', [
            'users' => $this->users(),
        ]);
    }
}
