<?php

declare(strict_types=1);

namespace App\Livewire\Contracts;

use App\Domain\Contract\Services\ContractAddendumService;
use App\Domain\Contract\Services\ContractService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\ContractAddendum;
use App\Models\Project;
use App\Models\TaxType;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Komponen nested — dirender di dalam tab "Kontrak" pada Projects\Show,
 * bukan komponen full-page (tidak punya route/layout sendiri).
 */
class Form extends Component
{
    public Project $project;

    public string $contract_number = '';

    public string $contract_date = '';

    public string $spmk_number = '';

    public string $spmk_date = '';

    public string $contract_value = '';

    public string $tax_type_id = '';

    public string $tax_amount = '';

    public string $net_value = '';

    public string $notes = '';

    public bool $hasContract = false;

    public bool $showAddendumForm = false;

    public string $addendum_number = '';

    public string $addendum_date = '';

    public string $reason = '';

    public string $new_contract_value = '';

    public function mount(Project $project): void
    {
        $this->project = $project;

        $this->authorize('update', $this->project);

        $contract = $project->contract;

        if ($contract) {
            $this->hasContract = true;
            $this->contract_number = $contract->contract_number;
            $this->contract_date = $contract->contract_date->toDateString();
            $this->spmk_number = (string) $contract->spmk_number;
            $this->spmk_date = $contract->spmk_date?->toDateString() ?? '';
            $this->contract_value = (string) $contract->contract_value;
            $this->tax_type_id = (string) $contract->tax_type_id;
            $this->tax_amount = (string) $contract->tax_amount;
            $this->net_value = (string) $contract->net_value;
            $this->notes = (string) $contract->notes;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'contract_number' => ['required', 'string', 'max:100'],
            'contract_date' => ['required', 'date'],
            'spmk_number' => ['nullable', 'string', 'max:100'],
            'spmk_date' => ['nullable', 'date'],
            'contract_value' => ['required', 'numeric', 'min:0'],
            'tax_type_id' => ['nullable', 'ulid', 'exists:tax_types,id'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'net_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Isi otomatis tax_amount/net_value sebagai DEFAULT saat jenis pajak
     * dipilih — bukan nilai yang dipaksakan, user tetap bisa mengubahnya
     * secara manual sesudahnya (lihat PROJECT_DECISIONS.md D-013).
     * Asumsi: contract_value bersifat tax-inclusive (nilai kontrak sudah
     * termasuk pajak), konsisten dengan cara ReferenceProjectSeeder
     * menghitung nilai kontrak RSPNDD.
     */
    public function updatedTaxTypeId(): void
    {
        if ($this->tax_type_id === '' || $this->contract_value === '') {
            return;
        }

        $taxType = TaxType::query()->whereKey($this->tax_type_id)->first();

        if (! $taxType || (float) $taxType->rate <= 0) {
            return;
        }

        $rate = (float) $taxType->rate;
        $contractValue = (float) $this->contract_value;

        $this->tax_amount = (string) round($contractValue * $rate / (100 + $rate), 2);
        $this->net_value = (string) round($contractValue - (float) $this->tax_amount, 2);
    }

    /**
     * @return Collection<int, TaxType>
     */
    public function taxTypeOptions(): Collection
    {
        return TaxType::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function save(ContractService $service): void
    {
        $this->authorize('update', $this->project);

        $data = $this->validate();

        foreach (['spmk_number', 'spmk_date', 'tax_type_id', 'tax_amount', 'net_value', 'notes'] as $nullableField) {
            $data[$nullableField] = $data[$nullableField] !== '' ? $data[$nullableField] : null;
        }

        // Setelah kontrak ada, nilai kontrak HANYA boleh berubah lewat
        // alur Adendum (di bawah) — bukan diedit bebas di form ini,
        // supaya setiap perubahan nilai selalu tercatat alasannya
        // (PROJECT_DECISIONS.md D-026). Dicek di server, bukan cuma
        // `disabled` di HTML, karena state Livewire tidak benar-benar
        // dikunci oleh atribut `disabled` semata.
        if ($this->hasContract) {
            unset($data['contract_value']);
        }

        $service->save($this->project, $data);

        session()->flash('status', 'Data kontrak berhasil disimpan.');
        $this->project->refresh();
        $this->contract_value = (string) $this->project->contract?->contract_value;
    }

    public function openAddendumForm(): void
    {
        $this->showAddendumForm = true;
        $this->reset(['addendum_number', 'addendum_date', 'reason', 'new_contract_value']);
        $this->resetErrorBag();
    }

    public function cancelAddendumForm(): void
    {
        $this->showAddendumForm = false;
        $this->reset(['addendum_number', 'addendum_date', 'reason', 'new_contract_value']);
        $this->resetErrorBag();
    }

    public function saveAddendum(ContractAddendumService $service): void
    {
        $this->authorize('update', $this->project);

        $contract = $this->project->contract;

        if ($contract === null) {
            $this->addError('reason', 'Simpan data kontrak terlebih dahulu sebelum menambah adendum.');

            return;
        }

        $data = $this->validate([
            'addendum_number' => ['nullable', 'string', 'max:100'],
            'addendum_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
            'new_contract_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        /** @var User $creator */
        $creator = Auth::user();

        $service->create($contract, [
            'addendum_number' => $data['addendum_number'] ?: null,
            'addendum_date' => $data['addendum_date'],
            'reason' => $data['reason'],
            'new_value' => $data['new_contract_value'] !== '' ? $data['new_contract_value'] : null,
        ], $creator);

        $this->project->refresh();
        $this->contract_value = (string) $this->project->contract?->contract_value;
        $this->cancelAddendumForm();
        session()->flash('status', 'Adendum kontrak berhasil ditambahkan.');
    }

    public function deleteAddendum(string $addendumId, ContractAddendumService $service): void
    {
        $this->authorize('update', $this->project);

        $contract = $this->project->contract;
        $addendum = $contract?->addenda()->findOrFail($addendumId) ?? abort(404);

        try {
            $service->delete($addendum);
            $this->project->refresh();
            $this->contract_value = (string) $this->project->contract?->contract_value;
            session()->flash('status', 'Adendum kontrak berhasil dihapus.');
        } catch (DomainActionException $exception) {
            session()->flash('addendum_error', $exception->getMessage());
        }
    }

    /**
     * Diurutkan dari yang PALING BARU DIBUAT (bukan `addendum_date`,
     * yang bisa diisi mundur/tidak berurutan oleh user) — supaya baris
     * teratas selalu konsisten dengan adendum yang boleh dihapus
     * (lihat ContractAddendumService::delete()).
     *
     * @return Collection<int, ContractAddendum>
     */
    public function addenda(): Collection
    {
        return $this->project->contract?->addenda()->orderByDesc('id')->get() ?? collect();
    }

    public function render(): View
    {
        return view('livewire.contracts.form', [
            'taxTypeOptions' => $this->taxTypeOptions(),
        ]);
    }
}
