<div class="space-y-4">
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-900">Kelengkapan Checklist</p>
                <p class="text-xs text-slate-500">{{ $fulfilled }} dari {{ $total }} dokumen lengkap</p>
            </div>
            <p class="text-lg font-semibold text-slate-900">
                {{ $total > 0 ? round(($fulfilled / $total) * 100) : 0 }}%
            </p>
        </div>
        <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-emerald-500" style="width: {{ $total > 0 ? round(($fulfilled / $total) * 100) : 0 }}%"></div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Kategori</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Dokumen</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Status</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($items as $item)
                    <tr wire:key="checklist-{{ $item->id }}">
                        <td class="px-4 py-3 text-slate-500">{{ $item->documentRequirement->category ?? '—' }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $item->documentRequirement->name }}</td>
                        <td class="px-4 py-3">
                            <x-checklist-status-badge :status="$item->status" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                @if ($item->status !== \App\Domain\DocumentRequirement\Enums\ChecklistStatus::Fulfilled)
                                    <button type="button" wire:click="setStatus('{{ $item->id }}', 'fulfilled')" class="text-emerald-600 hover:underline">
                                        Tandai Lengkap
                                    </button>
                                @endif
                                @if ($item->status !== \App\Domain\DocumentRequirement\Enums\ChecklistStatus::Missing)
                                    <button type="button" wire:click="setStatus('{{ $item->id }}', 'missing')" class="text-slate-500 hover:underline">
                                        Tandai Belum Lengkap
                                    </button>
                                @endif
                                @if ($item->status !== \App\Domain\DocumentRequirement\Enums\ChecklistStatus::NotApplicable)
                                    <button type="button" wire:click="setStatus('{{ $item->id }}', 'not_applicable')" class="text-slate-400 hover:underline">
                                        Tidak Berlaku
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-400">
                            Tidak ada dokumen yang berlaku untuk project ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
