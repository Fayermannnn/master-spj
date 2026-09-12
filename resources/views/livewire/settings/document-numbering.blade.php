<div class="mx-auto max-w-2xl space-y-6">
    <h1 class="text-lg font-semibold text-slate-900">Penomoran Dokumen</h1>
    <p class="text-sm text-slate-500">
        Format nomor surat resmi yang diisi otomatis ke placeholder <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">document.number</code>
        saat generate dokumen — berlaku untuk seluruh dokumen organisasi <strong>{{ $organization->name }}</strong>.
    </p>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        @if ($status)
            <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ $status }}
            </div>
        @endif

        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Format Nomor</label>
                <input type="text" wire:model.live="format_template" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm font-mono">
                @error('format_template') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-slate-400">
                    Token yang tersedia: <code>{seq}</code> (nomor urut, 3 digit), <code>{org}</code> (kode organisasi),
                    <code>{month}</code>, <code>{month_roman}</code>, <code>{year}</code>.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Reset Nomor Urut</label>
                    <select wire:model.live="reset_period" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                        <option value="never">Tidak pernah</option>
                        <option value="yearly">Setiap tahun</option>
                        <option value="monthly">Setiap bulan</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Nomor Urut Berikutnya</label>
                    <input type="number" min="1" wire:model.live="next_sequence" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    @error('next_sequence') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                <span class="text-xs font-medium uppercase tracking-wide text-slate-400">Pratinjau nomor berikutnya:</span>
                <span class="ml-1 font-mono font-semibold text-slate-900">{{ $this->previewNext() }}</span>
            </div>

            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Simpan Pengaturan
            </button>
        </form>
    </div>
</div>
