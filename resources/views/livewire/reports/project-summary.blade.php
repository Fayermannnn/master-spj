<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold text-slate-900">Laporan Ringkasan Project</h1>
        <div class="flex gap-2">
            <a href="{{ route('reports.project-summary.download', array_merge(['format' => 'xlsx'], $this->filters())) }}" class="rounded-md border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Unduh Excel
            </a>
            <a href="{{ route('reports.project-summary.download', array_merge(['format' => 'pdf'], $this->filters())) }}" class="rounded-md border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Unduh PDF
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-500">Cari</label>
            <input type="text" wire:model.live.debounce.400ms="search" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm" placeholder="Nama atau kode project">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Status</label>
            <select wire:model.live="status" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                <option value="">Semua Status</option>
                @foreach ($statuses as $option)
                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Klien</label>
            <select wire:model.live="clientId" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                <option value="">Semua Klien</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Jumlah Project</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $totals['count'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Nilai Kontrak</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">Rp {{ number_format($totals['contract_value'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Dibayar</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">Rp {{ number_format($totals['total_paid'], 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Kode</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Nama Project</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Klien</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Status</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Nilai Kontrak</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Total Dibayar</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Kelengkapan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rows as $row)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $row['code'] }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $row['name'] }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $row['client'] }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $row['status'] }}</td>
                        <td class="px-4 py-3 text-right text-slate-900">
                            {{ $row['contract_value'] !== null ? 'Rp '.number_format((float) $row['contract_value'], 0, ',', '.') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right text-slate-900">Rp {{ number_format($row['total_paid'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-slate-500">{{ $row['checklist_fulfilled'] }}/{{ $row['checklist_total'] }} ({{ $row['checklist_percentage'] }}%)</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400">Tidak ada project yang cocok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $rows->links() }}
</div>
