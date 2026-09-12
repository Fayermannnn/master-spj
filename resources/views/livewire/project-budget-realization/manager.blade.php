<div class="space-y-4">
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between text-sm">
            <p class="text-slate-500">Total realisasi: Rp {{ number_format($totals['realized'], 0, ',', '.') }} dari Rp {{ number_format($totals['budgeted'], 0, ',', '.') }} anggaran</p>
            <p class="font-semibold text-slate-900">{{ $totals['percentage'] }}%</p>
        </div>
        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full {{ $totals['percentage'] > 100 ? 'bg-amber-500' : 'bg-emerald-500' }}" style="width: {{ min($totals['percentage'], 100) }}%"></div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-slate-500">Kategori Biaya</th>
                    <th class="px-4 py-2 text-right font-medium text-slate-500">Anggaran</th>
                    <th class="px-4 py-2 text-right font-medium text-slate-500">Realisasi</th>
                    <th class="px-4 py-2 text-right font-medium text-slate-500">%</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rows as $row)
                    <tr wire:key="realization-{{ $row['category_id'] ?? 'none' }}">
                        <td class="px-4 py-2 text-slate-700">{{ $row['category_name'] }}</td>
                        <td class="px-4 py-2 text-right text-slate-700">Rp {{ number_format($row['budgeted'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right text-slate-700">Rp {{ number_format($row['realized'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right font-medium {{ $row['percentage'] > 100 ? 'text-amber-600' : 'text-slate-700' }}">{{ $row['percentage'] }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-400">Belum ada item biaya untuk project ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-slate-400">
        Realisasi dihitung dari alokasi termin ke item biaya (tab "Termin"). Realisasi di atas 100% berarti pembayaran yang dialokasikan ke kategori ini melebihi anggaran yang direncanakan.
    </p>
</div>
