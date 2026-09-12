<?php

declare(strict_types=1);

namespace App\Livewire\Contracts;

use App\Domain\Contract\Services\ContractService;
use App\Models\Project;
use App\Models\TaxType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
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

    public function mount(Project $project): void
    {
        $this->project = $project;

        $this->authorize('update', $this->project);

        $contract = $project->contract;

        if ($contract) {
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
        $data['tax_type_id'] = $data['tax_type_id'] ?: null;

        $service->save($this->project, $data);

        session()->flash('status', 'Data kontrak berhasil disimpan.');
        $this->project->refresh();
    }

    public function render(): View
    {
        return view('livewire.contracts.form', [
            'taxTypeOptions' => $this->taxTypeOptions(),
        ]);
    }
}
