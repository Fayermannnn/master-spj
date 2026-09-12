<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <input
            wire:model.live.debounce.400ms="search"
            type="search"
            placeholder="Cari nama atau kode project…"
            class="w-full max-w-xs rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
        >

        @can('create', \App\Models\Project::class)
            <a
                href="{{ route('projects.create') }}"
                wire:navigate
                class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
            >
                + Project Baru
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
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Nama Project</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Jenis</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Klien</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Status</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($projects as $project)
                    <tr wire:key="project-{{ $project->id }}">
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $project->code }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">
                            <a href="{{ route('projects.show', $project) }}" wire:navigate class="hover:underline">
                                {{ $project->name }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $project->projectType->name }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $project->client->name }}</td>
                        <td class="px-4 py-3">
                            <x-project-status-badge :status="$project->status" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('projects.show', $project) }}" wire:navigate class="text-blue-600 hover:underline">
                                    Lihat
                                </a>
                                @can('delete', $project)
                                    <button
                                        type="button"
                                        wire:click="delete('{{ $project->id }}')"
                                        wire:confirm="Hapus project \"{{ $project->name }}\"?"
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
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada project.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $projects->links() }}
</div>
