<?php

declare(strict_types=1);

namespace App\Livewire\ProjectPayments;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\Payment;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Komponen nested — dirender di dalam tab "Termin" pada Projects\Show.
 * Akses diatur lewat ProjectPolicy, sama seperti manager lain di project.
 */
class Manager extends Component
{
    public Project $project;

    public ?Payment $editing = null;

    public string $termin_number = '';

    public string $name = '';

    public string $percentage = '';

    public string $amount = '';

    public string $target_date = '';

    public string $trigger = '';

    public string $required_items = '';

    public bool $override_limit = false;

    public string $notes = '';

    public function mount(Project $project): void
    {
        $this->project = $project;

        $this->authorize('update', $this->project);

        $this->termin_number = (string) ($this->project->payments()->max('termin_number') + 1);
        $this->name = "Termin {$this->termin_number}";
    }

    public function edit(string $paymentId): void
    {
        $this->authorize('update', $this->project);

        $payment = $this->project->payments()->findOrFail($paymentId);

        $this->editing = $payment;
        $this->termin_number = (string) $payment->termin_number;
        $this->name = $payment->name;
        $this->percentage = (string) $payment->percentage;
        $this->amount = (string) $payment->amount;
        $this->target_date = $payment->target_date?->toDateString() ?? '';
        $this->trigger = (string) $payment->trigger;
        $this->required_items = (string) $payment->required_items;
        $this->notes = (string) $payment->notes;
    }

    public function cancelEdit(): void
    {
        $this->reset([
            'editing', 'percentage', 'amount', 'target_date', 'trigger',
            'required_items', 'override_limit', 'notes',
        ]);
        $this->termin_number = (string) ($this->project->payments()->max('termin_number') + 1);
        $this->name = "Termin {$this->termin_number}";
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'termin_number' => [
                'required', 'integer', 'min:1',
                Rule::unique('payments', 'termin_number')
                    ->where('project_id', $this->project->id)
                    ->ignore($this->editing?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'target_date' => ['nullable', 'date'],
            'trigger' => ['nullable', 'string', 'max:1000'],
            'required_items' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function save(PaymentService $service): void
    {
        $this->authorize('update', $this->project);

        $data = $this->validate();
        $data['target_date'] = $data['target_date'] ?: null;
        $data['percentage'] = $data['percentage'] !== '' ? $data['percentage'] : null;

        try {
            if ($this->editing) {
                $service->update($this->editing, $data, $this->override_limit);
                session()->flash('status', 'Termin berhasil diperbarui.');
            } else {
                $service->create($this->project, $data, $this->override_limit);
                session()->flash('status', 'Termin berhasil ditambahkan.');
            }

            $this->cancelEdit();
        } catch (DomainActionException $exception) {
            $this->addError('amount', $exception->getMessage());
        }
    }

    public function delete(string $paymentId, PaymentService $service): void
    {
        $this->authorize('update', $this->project);

        $payment = $this->project->payments()->findOrFail($paymentId);
        $service->delete($payment);

        session()->flash('status', 'Termin berhasil dihapus.');
    }

    public function transitionTo(string $paymentId, string $status, PaymentService $service): void
    {
        $this->authorize('update', $this->project);

        $payment = $this->project->payments()->findOrFail($paymentId);

        try {
            $updated = $service->transitionStatus($payment, PaymentStatus::from($status));
            session()->flash('status', "Status termin diubah menjadi \"{$updated->status->label()}\".");
        } catch (DomainActionException $exception) {
            $this->addError('status', $exception->getMessage());
        }
    }

    /**
     * @return list<PaymentStatus>
     */
    public function availableTransitions(Payment $payment): array
    {
        $targets = PaymentStatus::allowedTransitions()[$payment->status->value];

        return array_map(fn (string $value) => PaymentStatus::from($value), $targets);
    }

    public function render(): View
    {
        return view('livewire.project-payments.manager', [
            'payments' => $this->project->payments()->orderBy('termin_number')->get(),
        ]);
    }
}
