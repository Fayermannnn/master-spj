<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Domain\Identity\Enums\RoleName;
use App\Domain\Identity\Services\UserService;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $organization_id = null;

    /** @var list<string> */
    public array $selectedRoles = [];

    public bool $is_active = true;

    public function mount(?User $user = null): void
    {
        $this->user = $user?->exists ? $user : null;

        $this->authorize($this->user ? 'update' : 'create', $this->user ?? User::class);

        /** @var User $viewer */
        $viewer = Auth::user();

        if ($this->user) {
            $this->name = $this->user->name;
            $this->email = $this->user->email;
            $this->organization_id = $this->user->organization_id;
            $this->is_active = $this->user->is_active;
            $this->selectedRoles = array_values(
                $this->user->roles
                    ->map(static fn (Model $role): string => (string) $role->getAttribute('name'))
                    ->all()
            );
        } elseif (! $viewer->hasRole(RoleName::SuperAdmin->value)) {
            $this->organization_id = $viewer->organization_id;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->user?->id),
            ],
            'password' => [$this->user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'organization_id' => ['nullable', 'ulid', 'exists:organizations,id'],
            'selectedRoles' => ['required', 'array', 'min:1'],
            'selectedRoles.*' => ['string', Rule::in($this->assignableRoles()->pluck('name'))],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return Collection<int, Role>
     */
    public function assignableRoles(): Collection
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        $roles = Role::query()->orderBy('name')->get();

        if ($viewer->hasRole(RoleName::SuperAdmin->value)) {
            return $roles;
        }

        return $roles->reject(fn (Role $role) => $role->name === RoleName::SuperAdmin->value)->values();
    }

    /**
     * @return Collection<int, Organization>
     */
    public function organizationOptions(): Collection
    {
        return Organization::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function save(UserService $service): void
    {
        // Re-authorize di sini (bukan cuma di mount()): properti model publik
        // Livewire bisa di-tamper client-side antar request untuk menunjuk ke
        // record lain sebelum aksi mutasi dipanggil.
        $this->authorize($this->user ? 'update' : 'create', $this->user ?? User::class);

        $data = $this->validate();
        $roles = $data['selectedRoles'];
        unset($data['selectedRoles']);

        /** @var User $viewer */
        $viewer = Auth::user();

        if (! $viewer->hasRole(RoleName::SuperAdmin->value)) {
            // Non-super-admin tidak boleh memindahkan user ke organisasi lain,
            // walau memanipulasi payload request secara langsung.
            $data['organization_id'] = $viewer->organization_id;
        }

        if ($this->user) {
            $service->update($this->user, $data, $roles);
            session()->flash('status', "Pengguna \"{$this->name}\" berhasil diperbarui.");
        } else {
            $service->create($data, $roles);
            session()->flash('status', "Pengguna \"{$this->name}\" berhasil dibuat.");
        }

        $this->redirect(route('users.index'), navigate: true);
    }

    public function render(): View
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return view('livewire.users.form', [
            'assignableRoles' => $this->assignableRoles(),
            'organizationOptions' => $this->organizationOptions(),
            'canChooseOrganization' => $viewer->hasRole(RoleName::SuperAdmin->value),
        ])->title($this->user ? 'Ubah Pengguna' : 'Pengguna Baru');
    }
}
