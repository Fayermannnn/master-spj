<div class="relative" x-data="{ open: false }" @click.away="open = false">
    <button
        type="button"
        @click="open = !open"
        class="relative rounded p-2 text-slate-500 hover:bg-slate-100"
        aria-label="Notifikasi"
    >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
        @if ($alerts->isNotEmpty())
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                {{ $alerts->count() }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        class="absolute right-0 z-20 mt-2 w-80 rounded-lg border border-slate-200 bg-white shadow-lg"
    >
        <div class="border-b border-slate-100 px-4 py-3">
            <p class="text-sm font-semibold text-slate-900">Notifikasi</p>
        </div>

        <div class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
            @forelse ($alerts as $alert)
                <div class="flex items-start justify-between gap-2 px-4 py-3" wire:key="alert-{{ $alert['key'] }}">
                    <a href="{{ $alert['url'] }}" wire:navigate class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-900">{{ $alert['title'] }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $alert['message'] }}</p>
                    </a>
                    <button
                        type="button"
                        wire:click="dismiss('{{ $alert['key'] }}')"
                        class="shrink-0 text-xs text-slate-400 hover:text-slate-600"
                        aria-label="Tutup notifikasi"
                    >
                        &times;
                    </button>
                </div>
            @empty
                <p class="px-4 py-6 text-center text-sm text-slate-400">Tidak ada notifikasi.</p>
            @endforelse
        </div>
    </div>
</div>
