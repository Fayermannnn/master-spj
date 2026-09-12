@props(['status'])

@php
    $classes = match ($status) {
        \App\Domain\DocumentTemplate\Enums\TemplateStatus::Draft => 'bg-slate-100 text-slate-600',
        \App\Domain\DocumentTemplate\Enums\TemplateStatus::Active => 'bg-emerald-50 text-emerald-700',
        \App\Domain\DocumentTemplate\Enums\TemplateStatus::Archived => 'bg-slate-200 text-slate-500',
    };
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $classes }}">
    {{ $status->label() }}
</span>
