<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Domain\Organization\Services\OrganizationService;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Halaman pengaturan logo/kop surat — SELALU beroperasi terhadap
 * organisasi milik user yang sedang login, pola sama
 * `Settings\DocumentNumbering` (D-029)/`Profile\Edit` (D-023): tidak
 * ada celah IDOR karena tidak pernah menerima organization ID dari
 * route. Logo dipakai sebagai `organization.logo` — placeholder
 * RESERVED yang auto-inject di `DocumentGeneratorService`, sama
 * seperti `document.number` (PROJECT_DECISIONS.md D-030).
 */
#[Layout('layouts.app', ['title' => 'Kop Surat'])]
class Letterhead extends Component
{
    use WithFileUploads;

    public Organization $organization;

    public ?UploadedFile $logo = null;

    public ?string $status = null;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        abort_if($user->organization === null, 404);

        $this->organization = $user->organization;

        $this->authorize('update', $this->organization);
    }

    public function save(OrganizationService $service): void
    {
        $this->authorize('update', $this->organization);

        $this->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        /** @var UploadedFile $logo */
        $logo = $this->logo;

        $service->updateLogo($this->organization, $logo);

        $this->reset(['logo']);
        $this->organization->refresh();
        $this->status = 'Logo berhasil disimpan.';
    }

    public function remove(OrganizationService $service): void
    {
        $this->authorize('update', $this->organization);

        $service->removeLogo($this->organization);

        $this->organization->refresh();
        $this->status = 'Logo berhasil dihapus.';
    }

    public function render(): View
    {
        return view('livewire.settings.letterhead');
    }
}
