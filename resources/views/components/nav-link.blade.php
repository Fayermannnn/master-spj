@props(['href', 'active' => false])

<a
    href="{{ $href }}"
    {{ $attributes->merge([
        'class' => 'mb-0.5 flex items-center rounded-md px-3 py-2 font-medium transition-colors '
            . ($active
                ? 'bg-slate-800 text-white'
                : 'text-slate-300 hover:bg-slate-800/60 hover:text-white'),
    ]) }}
>
    {{ $slot }}
</a>
