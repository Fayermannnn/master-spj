<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <input
            wire:model.live.debounce.400ms="search"
            type="search"
            placeholder="Cari nama personel…"
            class="w-full max-w-xs rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
        >

        @can('create', \App\Models\Personnel::class)
            <a
                href="{{ route('personnel.create') }}"
                wire:navigate
                class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
            >
                + Personel Baru
            </a>
        @endcan
    </div>

    @error('delete')
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $message }}
        </div>
    @enderror

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Nama</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Kategori</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Posisi</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Status</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($personnel as $person)
                    <tr wire:key="personnel-{{ $person->id }}">
                        <td class="px-4 py-3 font-medium text-slate-900">
                            <a href="{{ route('personnel.show', $person) }}" wire:navigate class="hover:underline">
                                {{ $person->name }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $person->category->name }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $person->position ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-status-badge :active="$person->is_active" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('personnel.show', $person) }}" wire:navigate class="text-blue-600 hover:underline">
                                    Lihat
                                </a>
                                @can('delete', $person)
                                    <button
                                        type="button"
                                        wire:click="delete('{{ $person->id }}')"
                                        wire:confirm="Hapus personel \"{{ $person->name }}\"?"
                                        class="text-red-600 hover:underline"
                                    >
                                        Hapus
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">Belum ada personel.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $personnel->links() }}
</div>
