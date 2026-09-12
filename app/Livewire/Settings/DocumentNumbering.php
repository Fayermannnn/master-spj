<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Domain\Settings\Services\NumberingService;
use App\Domain\Settings\Services\NumberingSettingService;
use App\Models\NumberingSetting;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Halaman pengaturan penomoran dokumen resmi — SELALU beroperasi
 * terhadap organisasi milik user yang sedang login (`Auth::user()->organization`),
 * TIDAK PERNAH menerima organization ID dari route/request (pola sama
 * `Profile\Edit`, D-023 — tidak ada celah IDOR untuk digerbangi selain
 * memastikan user memang admin organisasinya sendiri lewat
 * `OrganizationPolicy::update`, bukan permission baru). Mengisi domain
 * Settings yang sejak Phase 0 cuma README (PROJECT_DECISIONS.md D-029).
 */
#[Layout('layouts.app', ['title' => 'Penomoran Dokumen'])]
class DocumentNumbering extends Component
{
    public Organization $organization;

    public string $format_template = '{seq}/SPJ/{org}/{month_roman}/{year}';

    public string $reset_period = 'yearly';

    public string $next_sequence = '1';

    public ?string $status = null;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        abort_if($user->organization === null, 404);

        $this->organization = $user->organization;

        $this->authorize('update', $this->organization);

        $setting = $this->organization->numberingSetting;

        if ($setting !== null) {
            $this->format_template = $setting->format_template;
            $this->reset_period = $setting->reset_period;
            $this->next_sequence = (string) $setting->next_sequence;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'format_template' => ['required', 'string', 'max:255'],
            'reset_period' => ['required', Rule::in(['never', 'yearly', 'monthly'])],
            'next_sequence' => ['required', 'integer', 'min:1'],
        ];
    }

    public function save(NumberingSettingService $service): void
    {
        $this->authorize('update', $this->organization);

        $data = $this->validate();

        $service->save($this->organization, $data);

        $this->status = 'Pengaturan penomoran dokumen berhasil disimpan.';
    }

    /**
     * Dipanggil langsung dari Blade (`$this->previewNext()`), BUKAN
     * lewat aksi Livewire — resolve service manual lewat `app()`
     * karena panggilan method langsung dari view tidak melalui
     * mekanisme method-injection Livewire.
     */
    public function previewNext(): string
    {
        $setting = new NumberingSetting([
            'format_template' => $this->format_template,
            'reset_period' => $this->reset_period,
            'next_sequence' => is_numeric($this->next_sequence) ? (int) $this->next_sequence : 1,
            'last_reset_period_key' => $this->organization->numberingSetting?->last_reset_period_key,
        ]);

        return app(NumberingService::class)->previewNext($setting, $this->organization);
    }

    public function render(): View
    {
        return view('livewire.settings.document-numbering');
    }
}
