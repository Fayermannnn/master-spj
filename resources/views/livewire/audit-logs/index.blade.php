<div class="space-y-4">
    <h1 class="text-lg font-semibold text-slate-900">Audit Log</h1>

    <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-4">
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Modul</label>
            <select wire:model.live="module" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                <option value="">Semua Modul</option>
                @foreach ($moduleOptions as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Pengguna</label>
            <select wire:model.live="userId" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                <option value="">Semua Pengguna</option>
                @foreach ($userOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Dari Tanggal</label>
            <input type="date" wire:model.live="dateFrom" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Sampai Tanggal</label>
            <input type="date" wire:model.live="dateTo" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Waktu</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Pengguna</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Modul</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Aksi</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Subjek</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr wire:key="log-{{ $log->id }}">
                        <td class="px-4 py-3 text-slate-500">{{ $log->created_at?->translatedFormat('d M Y H:i') }}</td>
                        <td class="px-4 py-3 text-slate-900">{{ $log->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $log->module }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $log->action }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-400">
                            {{ $log->auditable_type ? class_basename($log->auditable_type) : '—' }}
                            @if ($log->auditable_id)
                                #{{ \Illuminate\Support\Str::limit($log->auditable_id, 8, '') }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="toggleDetail('{{ $log->id }}')" class="text-blue-600 hover:underline">
                                {{ $expandedLogId === $log->id ? 'Sembunyikan' : 'Lihat' }}
                            </button>
                        </td>
                    </tr>
                    @if ($expandedLogId === $log->id)
                        <tr wire:key="log-detail-{{ $log->id }}">
                            <td colspan="6" class="bg-slate-50 px-4 py-4">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Sebelum</p>
                                        <pre class="overflow-x-auto rounded-md border border-slate-200 bg-white p-3 text-xs text-slate-700">{{ $log->before ? json_encode($log->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—' }}</pre>
                                    </div>
                                    <div>
                                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Sesudah</p>
                                        <pre class="overflow-x-auto rounded-md border border-slate-200 bg-white p-3 text-xs text-slate-700">{{ $log->after ? json_encode($log->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—' }}</pre>
                                    </div>
                                </div>
                                <p class="mt-3 text-xs text-slate-400">IP: {{ $log->ip_address ?? '—' }}</p>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">Tidak ada entri audit log.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $logs->links() }}
</div>
