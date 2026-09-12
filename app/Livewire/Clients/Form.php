<?php

declare(strict_types=1);

namespace App\Livewire\Clients;

use App\Domain\Client\Enums\ContactType;
use App\Domain\Client\Services\ClientService;
use App\Domain\Identity\Enums\RoleName;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Client $client = null;

    public string $name = '';

    public string $address = '';

    public string $phone = '';

    public string $email = '';

    public string $notes = '';

    public ?string $organization_id = null;

    public bool $is_active = true;

    /** @var array<int, array{id: string|null, type: string, name: string, position: string, phone: string, email: string}> */
    public array $contacts = [];

    public function mount(?Client $client = null): void
    {
        $this->client = $client?->exists ? $client : null;

        $this->authorize($this->client ? 'update' : 'create', $this->client ?? Client::class);

        /** @var User $viewer */
        $viewer = Auth::user();

        if ($this->client) {
            $this->name = $this->client->name;
            $this->address = (string) $this->client->address;
            $this->phone = (string) $this->client->phone;
            $this->email = (string) $this->client->email;
            $this->notes = (string) $this->client->notes;
            $this->organization_id = $this->client->organization_id;
            $this->is_active = $this->client->is_active;

            $this->contacts = $this->client->contacts->map(fn ($contact): array => [
                'id' => (string) $contact->id,
                'type' => (string) $contact->type->value,
                'name' => (string) $contact->name,
                'position' => (string) $contact->position,
                'phone' => (string) $contact->phone,
                'email' => (string) $contact->email,
            ])->values()->all();
        } elseif (! $viewer->hasRole(RoleName::SuperAdmin->value)) {
            $this->organization_id = $viewer->organization_id;
        }

        if ($this->contacts === []) {
            $this->addContact();
        }
    }

    public function addContact(): void
    {
        $this->contacts[] = [
            'id' => null,
            'type' => ContactType::Ppk->value,
            'name' => '',
            'position' => '',
            'phone' => '',
            'email' => '',
        ];
    }

    public function removeContact(int $index): void
    {
        $this->contacts = collect($this->contacts)
            ->reject(fn (array $contact, int $i): bool => $i === $index)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'organization_id' => ['nullable', 'ulid', 'exists:organizations,id'],
            'is_active' => ['boolean'],
            'contacts' => ['array'],
            'contacts.*.type' => ['required', Rule::in(array_map(fn ($case) => $case->value, ContactType::cases()))],
            'contacts.*.name' => ['required', 'string', 'max:255'],
            'contacts.*.position' => ['nullable', 'string', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:30'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * @return Collection<int, Organization>
     */
    public function organizationOptions(): Collection
    {
        return Organization::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function save(ClientService $service): void
    {
        $this->authorize($this->client ? 'update' : 'create', $this->client ?? Client::class);

        $data = $this->validate();
        $contacts = $data['contacts'];
        unset($data['contacts']);

        /** @var User $viewer */
        $viewer = Auth::user();

        if (! $viewer->hasRole(RoleName::SuperAdmin->value)) {
            $data['organization_id'] = $viewer->organization_id;
        }

        if ($this->client) {
            $service->update($this->client, $data, $contacts);
            session()->flash('status', "Klien \"{$this->name}\" berhasil diperbarui.");
        } else {
            $service->create($data, $contacts);
            session()->flash('status', "Klien \"{$this->name}\" berhasil dibuat.");
        }

        $this->redirect(route('clients.index'), navigate: true);
    }

    public function render(): View
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return view('livewire.clients.form', [
            'contactTypes' => ContactType::cases(),
            'organizationOptions' => $this->organizationOptions(),
            'canChooseOrganization' => $viewer->hasRole(RoleName::SuperAdmin->value),
        ])->title($this->client ? 'Ubah Klien' : 'Klien Baru');
    }
}
