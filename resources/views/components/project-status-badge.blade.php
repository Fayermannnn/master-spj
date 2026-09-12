@props(['status'])

@php
    $classes = match ($status) {
        \App\Domain\ProjectManagement\Enums\ProjectStatus::Draft => 'bg-slate-100 text-slate-600',
        \App\Domain\ProjectManagement\Enums\ProjectStatus::Preparation => 'bg-amber-50 text-amber-700',
        \App\Domain\ProjectManagement\Enums\ProjectStatus::Active => 'bg-blue-50 text-blue-700',
        \App\Domain\ProjectManagement\Enums\ProjectStatus::PaymentProcessing => 'bg-purple-50 text-purple-700',
        \App\Domain\ProjectManagement\Enums\ProjectStatus::Completed => 'bg-emerald-50 text-emerald-700',
        \App\Domain\ProjectManagement\Enums\ProjectStatus::Closed => 'bg-slate-200 text-slate-700',
        \App\Domain\ProjectManagement\Enums\ProjectStatus::Archived => 'bg-slate-100 text-slate-400',
    };
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $classes }}">
    {{ $status->label() }}
</span>
