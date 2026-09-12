<?php

declare(strict_types=1);

namespace App\Livewire\TaxTypes;

use App\Domain\Cost\Services\TaxTypeService;
use App\Models\TaxType;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?TaxType $taxType = null;

    public string $code = '';

    public string $name = '';

    public string $rate = '';

    public string $description = '';

    public bool $is_active = true;

    public function mount(?TaxType $taxType = null): void
    {
        $this->taxType = $taxType?->exists ? $taxType : null;

        $this->authorize($this->taxType ? 'update' : 'create', $this->taxType ?? TaxType::class);

        if ($this->taxType) {
            $this->code = $this->taxType->code;
            $this->name = $this->taxType->name;
            $this->rate = (string) $this->taxType->rate;
            $this->description = (string) $this->taxType->description;
            $this->is_active = $this->taxType->is_active;
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
                Rule::unique('tax_types', 'code')->ignore($this->taxType?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(TaxTypeService $service): void
    {
        $this->authorize($this->taxType ? 'update' : 'create', $this->taxType ?? TaxType::class);

        $data = $this->validate();

        if ($this->taxType) {
            $service->update($this->taxType, $data);
            session()->flash('status', "Jenis pajak \"{$this->name}\" berhasil diperbarui.");
        } else {
            $service->create($data);
            session()->flash('status', "Jenis pajak \"{$this->name}\" berhasil dibuat.");
        }

        $this->redirect(route('tax-types.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.tax-types.form')
            ->title($this->taxType ? 'Ubah Jenis Pajak' : 'Jenis Pajak Baru');
    }
}
