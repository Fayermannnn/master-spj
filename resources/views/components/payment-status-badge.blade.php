@props(['status'])

@php
    $classes = match ($status) {
        \App\Domain\Payment\Enums\PaymentStatus::Pending => 'bg-slate-100 text-slate-600',
        \App\Domain\Payment\Enums\PaymentStatus::Submitted => 'bg-amber-50 text-amber-700',
        \App\Domain\Payment\Enums\PaymentStatus::Approved => 'bg-blue-50 text-blue-700',
        \App\Domain\Payment\Enums\PaymentStatus::Rejected => 'bg-red-50 text-red-700',
        \App\Domain\Payment\Enums\PaymentStatus::Paid => 'bg-emerald-50 text-emerald-700',
    };
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $classes }}">
    {{ $status->label() }}
</span>
