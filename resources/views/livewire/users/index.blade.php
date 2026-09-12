<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <input
            wire:model.live.debounce.400ms="search"
            type="search"
            placeholder="Cari nama atau email…"
            class="w-full max-w-xs rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
        >

        @can('create', \App\Models\User::class)
            <a
                href="{{ route('users.create') }}"
                wire:navigate
                class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
            >
                + Pengguna Baru
            </a>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Nama</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Email</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Organisasi</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Role</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Status</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $user->organization?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500">
                            {{ $user->roles->map->name->implode(', ') ?: '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <x-status-badge :active="$user->is_active" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                @can('update', $user)
                                    <a href="{{ route('users.edit', $user) }}" wire:navigate class="text-blue-600 hover:underline">
                                        Ubah
                                    </a>
                                @endcan
                                @can('delete', $user)
                                    <button
                                        type="button"
                                        wire:click="delete('{{ $user->id }}')"
                                        wire:confirm="Hapus pengguna \"{{ $user->name }}\"? Tindakan ini tidak dapat dibatalkan."
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
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada pengguna.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
