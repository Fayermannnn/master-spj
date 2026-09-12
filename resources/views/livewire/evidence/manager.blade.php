<div class="space-y-4">
    @can('update', $project)
        <form wire:submit="upload" class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-4">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-500">Nama Bukti</label>
                <input type="text" wire:model="name" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm" placeholder="Mis. Foto Serah Terima Laporan">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Kategori</label>
                <input type="text" wire:model="category" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm" placeholder="Mis. Dokumentasi">
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">File (PDF/JPG/PNG/DOC/XLS, maks 10MB)</label>
                <input type="file" wire:model="file" class="block w-full text-sm">
                @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <div wire:loading wire:target="file" class="mt-1 text-xs text-slate-400">Mengunggah…</div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Terkait Termin</label>
                <select wire:model="payment_id" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    <option value="">—</option>
                    @foreach ($payments as $payment)
                        <option value="{{ $payment->id }}">Termin {{ $payment->termin_number }} — {{ $payment->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Terkait Personel</label>
                <select wire:model="personnel_id" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    <option value="">—</option>
                    @foreach ($personnelOptions as $personnel)
                        <option value="{{ $personnel->id }}">{{ $personnel->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Terkait Requirement</label>
                <select wire:model="document_requirement_id" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    <option value="">—</option>
                    @foreach ($requirements as $requirement)
                        <option value="{{ $requirement->id }}">{{ $requirement->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-4">
                <label class="mb-1 block text-xs font-medium text-slate-500">Catatan</label>
                <input type="text" wire:model="notes" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
            </div>

            <div class="sm:col-span-4">
                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Unggah
                </button>
            </div>
        </form>
    @endcan

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Nama</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Terkait</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Diunggah</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($evidences as $evidence)
                    <tr wire:key="evidence-{{ $evidence->id }}">
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900">{{ $evidence->name }}</p>
                            <p class="text-xs text-slate-400">{{ $evidence->category ?? '—' }} · {{ $evidence->original_filename }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-500">
                            @if ($evidence->payment)Termin {{ $evidence->payment->termin_number }}<br>@endif
                            @if ($evidence->personnel){{ $evidence->personnel->name }}<br>@endif
                            @if ($evidence->documentRequirement){{ $evidence->documentRequirement->name }}@endif
                            @if (!$evidence->payment && !$evidence->personnel && !$evidence->documentRequirement)—@endif
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $evidence->created_at->translatedFormat('d M Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('evidences.download', $evidence) }}" class="text-blue-600 hover:underline">Unduh</a>
                                @can('update', $project)
                                    <button
                                        type="button"
                                        wire:click="deleteEvidence('{{ $evidence->id }}')"
                                        wire:confirm="Hapus bukti \"{{ $evidence->name }}\"?"
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
                        <td colspan="4" class="px-4 py-8 text-center text-slate-400">Belum ada bukti pendukung.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
