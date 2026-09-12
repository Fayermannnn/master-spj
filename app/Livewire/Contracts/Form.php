<?php

declare(strict_types=1);

namespace App\Livewire\Contracts;

use App\Domain\Contract\Services\ContractService;
use App\Models\Project;
use Illuminate\Contracts\View\View;
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
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'net_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function save(ContractService $service): void
    {
        $this->authorize('update', $this->project);

        $data = $this->validate();

        $service->save($this->project, $data);

        session()->flash('status', 'Data kontrak berhasil disimpan.');
        $this->project->refresh();
    }

    public function render(): View
    {
        return view('livewire.contracts.form');
    }
}
