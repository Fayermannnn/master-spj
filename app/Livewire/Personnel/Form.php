<?php

declare(strict_types=1);

namespace App\Livewire\Personnel;

use App\Domain\Identity\Enums\RoleName;
use App\Domain\Personnel\Services\PersonnelService;
use App\Models\Organization;
use App\Models\Personnel;
use App\Models\PersonnelCategory;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Personnel $personnel = null;

    public ?string $organization_id = null;

    public string $personnel_category_id = '';

    public string $name = '';

    public string $position = '';

    public string $education = '';

    public string $expertise = '';

    public string $id_number = '';

    public string $npwp = '';

    public string $certificate_number = '';

    public string $certificate_expiry_date = '';

    public string $phone = '';

    public string $email = '';

    public string $default_rate = '';

    public bool $is_active = true;

    public string $notes = '';

    public function mount(?Personnel $personnel = null): void
    {
        $this->personnel = $personnel?->exists ? $personnel : null;

        $this->authorize($this->personnel ? 'update' : 'create', $this->personnel ?? Personnel::class);

        /** @var User $viewer */
        $viewer = Auth::user();

        if ($this->personnel) {
            $this->organization_id = $this->personnel->organization_id;
            $this->personnel_category_id = $this->personnel->personnel_category_id;
            $this->name = $this->personnel->name;
            $this->position = (string) $this->personnel->position;
            $this->education = (string) $this->personnel->education;
            $this->expertise = (string) $this->personnel->expertise;
            $this->id_number = (string) $this->personnel->id_number;
            $this->npwp = (string) $this->personnel->npwp;
            $this->certificate_number = (string) $this->personnel->certificate_number;
            $this->certificate_expiry_date = $this->personnel->certificate_expiry_date?->toDateString() ?? '';
            $this->phone = (string) $this->personnel->phone;
            $this->email = (string) $this->personnel->email;
            $this->default_rate = (string) $this->personnel->default_rate;
            $this->is_active = $this->personnel->is_active;
            $this->notes = (string) $this->personnel->notes;
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
            'organization_id' => ['required', 'ulid', 'exists:organizations,id'],
            'personnel_category_id' => ['required', 'ulid', 'exists:personnel_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', 'string', 'max:100'],
            'expertise' => ['nullable', 'string', 'max:1000'],
            'id_number' => ['nullable', 'string', 'max:30'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'certificate_number' => ['nullable', 'string', 'max:100'],
            'certificate_expiry_date' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'default_rate' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return Collection<int, Organization>
     */
    public function organizationOptions(): Collection
    {
        return Organization::query()->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * @return Collection<int, PersonnelCategory>
     */
    public function categoryOptions(): Collection
    {
        return PersonnelCategory::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function save(PersonnelService $service): void
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        if (! $viewer->hasRole(RoleName::SuperAdmin->value)) {
            $this->organization_id = $viewer->organization_id;
        }

        $this->authorize($this->personnel ? 'update' : 'create', $this->personnel ?? Personnel::class);

        $data = $this->validate();

        $data['certificate_expiry_date'] = $data['certificate_expiry_date'] ?: null;
        $data['default_rate'] = $data['default_rate'] !== '' ? $data['default_rate'] : null;

        if ($this->personnel) {
            $service->update($this->personnel, $data);
            session()->flash('status', "Personel \"{$this->name}\" berhasil diperbarui.");
            $this->redirect(route('personnel.show', $this->personnel), navigate: true);

            return;
        }

        $personnel = $service->create($data);
        session()->flash('status', "Personel \"{$this->name}\" berhasil dibuat.");
        $this->redirect(route('personnel.show', $personnel), navigate: true);
    }

    public function render(): View
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return view('livewire.personnel.form', [
            'organizationOptions' => $this->organizationOptions(),
            'categoryOptions' => $this->categoryOptions(),
            'canChooseOrganization' => $viewer->hasRole(RoleName::SuperAdmin->value),
        ])->title($this->personnel ? 'Ubah Personel' : 'Personel Baru');
    }
}
