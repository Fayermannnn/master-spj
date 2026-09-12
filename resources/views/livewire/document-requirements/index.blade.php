<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <input
            wire:model.live.debounce.400ms="search"
            type="search"
            placeholder="Cari nama atau kode kebutuhan dokumen…"
            class="w-full max-w-xs rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
        >

        @can('create', \App\Models\DocumentRequirement::class)
            <a
                href="{{ route('document-requirements.create') }}"
                wire:navigate
                class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
            >
                + Kebutuhan Dokumen Baru
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
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Kode</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Nama</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Jenis Project</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Kondisi</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Status</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($requirements as $requirement)
                    <tr wire:key="req-{{ $requirement->id }}">
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $requirement->code }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $requirement->name }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $requirement->projectType?->name ?? 'Semua Jenis' }}</td>
                        <td class="px-4 py-3 text-slate-500">
                            {{ $requirement->rules->where('is_active', true)->count() }} rule
                        </td>
                        <td class="px-4 py-3">
                            <x-status-badge :active="$requirement->is_active" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                @can('viewAny', \App\Models\DocumentTemplate::class)
                                    <a href="{{ route('document-templates.manage', $requirement) }}" wire:navigate class="text-slate-600 hover:underline">
                                        Template
                                    </a>
                                @endcan
                                @can('update', $requirement)
                                    <a href="{{ route('document-requirements.edit', $requirement) }}" wire:navigate class="text-blue-600 hover:underline">
                                        Ubah
                                    </a>
                                @endcan
                                @can('delete', $requirement)
                                    <button
                                        type="button"
                                        wire:click="delete('{{ $requirement->id }}')"
                                        wire:confirm="Hapus kebutuhan dokumen \"{{ $requirement->name }}\"?"
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
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada kebutuhan dokumen.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $requirements->links() }}
</div>
