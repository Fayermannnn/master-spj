<div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
    @if (session('status'))
        <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="contract_number" class="mb-1 block text-sm font-medium text-slate-700">Nomor Kontrak</label>
                <input wire:model="contract_number" id="contract_number" type="text" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('contract_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="contract_date" class="mb-1 block text-sm font-medium text-slate-700">Tanggal Kontrak</label>
                <input wire:model="contract_date" id="contract_date" type="date" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('contract_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="spmk_number" class="mb-1 block text-sm font-medium text-slate-700">Nomor SPMK</label>
                <input wire:model="spmk_number" id="spmk_number" type="text" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('spmk_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="spmk_date" class="mb-1 block text-sm font-medium text-slate-700">Tanggal SPMK</label>
                <input wire:model="spmk_date" id="spmk_date" type="date" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('spmk_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="contract_value" class="mb-1 block text-sm font-medium text-slate-700">Nilai Kontrak (Rp)</label>
                <input wire:model="contract_value" id="contract_value" type="number" step="0.01" @if($hasContract) disabled @endif class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:bg-slate-50 disabled:text-slate-400">
                @error('contract_value') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @if ($hasContract)
                    <p class="mt-1 text-xs text-slate-400">Ubah lewat "Adendum Kontrak" di bawah, bukan di sini — supaya setiap perubahan tercatat alasannya.</p>
                @endif
            </div>

            <div>
                <label for="tax_type_id" class="mb-1 block text-sm font-medium text-slate-700">Jenis Pajak</label>
                <select wire:model.live="tax_type_id" id="tax_type_id" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">— Tidak kena pajak —</option>
                    @foreach ($taxTypeOptions as $tax)
                        <option value="{{ $tax->id }}">{{ $tax->name }} ({{ rtrim(rtrim($tax->rate, '0'), '.') }}%)</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Mengisi otomatis kolom pajak/nilai bersih di bawah — tetap bisa diedit manual.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="tax_amount" class="mb-1 block text-sm font-medium text-slate-700">Pajak (Rp)</label>
                <input wire:model="tax_amount" id="tax_amount" type="number" step="0.01" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('tax_amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="net_value" class="mb-1 block text-sm font-medium text-slate-700">Nilai Bersih (Rp)</label>
                <input wire:model="net_value" id="net_value" type="number" step="0.01" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('net_value') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Catatan</label>
            <textarea wire:model="notes" id="notes" rows="2" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
            @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end border-t border-slate-100 pt-4">
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Simpan Kontrak
            </button>
        </div>
    </form>

    @if ($hasContract)
        <div class="mt-6 border-t border-slate-200 pt-6">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Adendum Kontrak</h3>
                @if (! $showAddendumForm)
                    <button type="button" wire:click="openAddendumForm" class="text-sm text-blue-600 hover:underline">
                        + Tambah Adendum
                    </button>
                @endif
            </div>

            @if (session('addendum_error'))
                <div class="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                    {{ session('addendum_error') }}
                </div>
            @endif

            @if ($showAddendumForm)
                <form wire:submit="saveAddendum" class="mt-4 grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">Nomor Adendum</label>
                        <input wire:model="addendum_number" type="text" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                        @error('addendum_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">Tanggal Adendum</label>
                        <input wire:model="addendum_date" type="date" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                        @error('addendum_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-slate-500">Alasan / Perubahan</label>
                        <textarea wire:model="reason" rows="2" placeholder="mis. Perpanjangan waktu pelaksanaan 30 hari kalender akibat kondisi lapangan" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm"></textarea>
                        @error('reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">Nilai Kontrak Baru (Rp, opsional)</label>
                        <input wire:model="new_contract_value" type="number" step="0.01" placeholder="Kosongkan jika nilai tidak berubah" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                        @error('new_contract_value') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                            Simpan Adendum
                        </button>
                        <button type="button" wire:click="cancelAddendumForm" class="rounded-md border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                    </div>
                </form>
            @endif

            <div class="mt-4 space-y-3">
                @forelse ($this->addenda() as $addendum)
                    <div wire:key="addendum-{{ $addendum->id }}" class="flex items-start justify-between rounded-md border border-slate-100 bg-white p-3 text-sm shadow-sm">
                        <div>
                            <p class="font-medium text-slate-700">
                                {{ $addendum->addendum_number ?? 'Tanpa nomor' }}
                                <span class="font-normal text-slate-400">&middot; {{ $addendum->addendum_date->translatedFormat('d M Y') }}</span>
                            </p>
                            <p class="mt-1 text-slate-600">{{ $addendum->reason }}</p>
                            @if ($addendum->new_value !== null)
                                <p class="mt-1 text-xs text-slate-400">
                                    Nilai kontrak: Rp {{ number_format((float) $addendum->previous_value, 0, ',', '.') }} &rarr; Rp {{ number_format((float) $addendum->new_value, 0, ',', '.') }}
                                </p>
                            @endif
                        </div>
                        <button
                            type="button"
                            wire:click="deleteAddendum('{{ $addendum->id }}')"
                            wire:confirm="Hapus adendum ini? Nilai kontrak akan dikembalikan ke sebelum adendum ini."
                            class="whitespace-nowrap text-xs text-red-600 hover:underline"
                        >
                            Hapus
                        </button>
                    </div>
                @empty
                    <p class="text-xs text-slate-400">Belum ada adendum untuk kontrak ini.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
