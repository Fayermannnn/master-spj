<?php

declare(strict_types=1);

namespace App\Livewire\Organizations;

use App\Domain\Organization\Services\OrganizationService;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Organization $organization = null;

    public string $code = '';

    public string $name = '';

    public string $npwp = '';

    public string $address = '';

    public string $phone = '';

    public string $email = '';

    public bool $is_active = true;

    public function mount(?Organization $organization = null): void
    {
        $this->organization = $organization?->exists ? $organization : null;

        $this->authorize($this->organization ? 'update' : 'create', $this->organization ?? Organization::class);

        if ($this->organization) {
            $this->code = $this->organization->code;
            $this->name = $this->organization->name;
            $this->npwp = (string) $this->organization->npwp;
            $this->address = (string) $this->organization->address;
            $this->phone = (string) $this->organization->phone;
            $this->email = (string) $this->organization->email;
            $this->is_active = $this->organization->is_active;
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
                Rule::unique('organizations', 'code')->ignore($this->organization?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(OrganizationService $service): void
    {
        // Re-authorize di sini (bukan cuma di mount()): properti model publik
        // Livewire bisa di-tamper client-side antar request untuk menunjuk ke
        // record lain sebelum aksi mutasi dipanggil.
        $this->authorize($this->organization ? 'update' : 'create', $this->organization ?? Organization::class);

        $data = $this->validate();

        if ($this->organization) {
            $service->update($this->organization, $data);
            session()->flash('status', "Organisasi \"{$this->name}\" berhasil diperbarui.");
        } else {
            $service->create($data);
            session()->flash('status', "Organisasi \"{$this->name}\" berhasil dibuat.");
        }

        $this->redirect(route('organizations.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.organizations.form')
            ->title($this->organization ? 'Ubah Organisasi' : 'Organisasi Baru');
    }
}
