@props(['status'])

@php
    $classes = match ($status) {
        \App\Domain\DocumentRequirement\Enums\ChecklistStatus::Missing => 'bg-red-50 text-red-700',
        \App\Domain\DocumentRequirement\Enums\ChecklistStatus::Fulfilled => 'bg-emerald-50 text-emerald-700',
        \App\Domain\DocumentRequirement\Enums\ChecklistStatus::NotApplicable => 'bg-slate-100 text-slate-500',
    };
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $classes }}">
    {{ $status->label() }}
</span>
