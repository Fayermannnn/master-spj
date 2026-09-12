<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <input
            wire:model.live.debounce.400ms="search"
            type="search"
            placeholder="Cari key atau label variabel…"
            class="w-full max-w-xs rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
        >

        @can('create', \App\Models\TemplateVariable::class)
            <a
                href="{{ route('template-variables.create') }}"
                wire:navigate
                class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
            >
                + Variabel Baru
            </a>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Key</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Label</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Tipe</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Status</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($variables as $variable)
                    @php $wrappedKey = '{{'.$variable->key.'}}'; @endphp
                    <tr wire:key="var-{{ $variable->id }}">
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $wrappedKey }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $variable->label }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $variable->data_type->label() }}</td>
                        <td class="px-4 py-3">
                            <x-status-badge :active="$variable->is_active" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                @can('update', $variable)
                                    <a href="{{ route('template-variables.edit', $variable) }}" wire:navigate class="text-blue-600 hover:underline">
                                        Ubah
                                    </a>
                                @endcan
                                @can('delete', $variable)
                                    <button
                                        type="button"
                                        wire:click="delete('{{ $variable->id }}')"
                                        wire:confirm="Hapus variabel \"{{ $variable->key }}\"?"
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
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">Belum ada variabel template.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $variables->links() }}
</div>
