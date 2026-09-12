@props(['active' => true, 'activeLabel' => 'Aktif', 'inactiveLabel' => 'Nonaktif'])

@if ($active)
    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
        {{ $activeLabel }}
    </span>
@else
    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
        {{ $inactiveLabel }}
    </span>
@endif
