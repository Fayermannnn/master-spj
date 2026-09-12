<?php

declare(strict_types=1);

namespace App\Livewire\Clients;

use App\Domain\Client\Services\ClientService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\Client;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Klien'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Client::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(string $clientId, ClientService $service): void
    {
        $client = Client::query()->findOrFail($clientId);
        $this->authorize('delete', $client);

        try {
            $service->delete($client);
            session()->flash('status', "Klien \"{$client->name}\" berhasil dihapus.");
        } catch (DomainActionException $exception) {
            $this->addError('delete', $exception->getMessage());
        }
    }

    /**
     * @return LengthAwarePaginator<int, Client>
     */
    public function clients(): LengthAwarePaginator
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return Client::query()
            ->when(! $viewer->hasRole('super_admin'), fn ($query) => $query->where('organization_id', $viewer->organization_id))
            ->when($this->search !== '', fn ($query) => $query->where('name', 'ilike', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.clients.index', [
            'clients' => $this->clients(),
        ]);
    }
}
