<div class="space-y-4">
    <form wire:submit="save" class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-6">
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Termin Ke-</label>
            <input wire:model="termin_number" type="number" min="1" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm" @if($editing) disabled @endif>
            @error('termin_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-500">Nama Termin</label>
            <input wire:model="name" type="text" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Persentase (%)</label>
            <input wire:model="percentage" type="number" step="0.01" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
        </div>

        <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-500">Nominal (Rp)</label>
            <input wire:model="amount" type="number" step="0.01" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
            @error('amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-500">Target Tanggal</label>
            <input wire:model="target_date" type="date" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
        </div>

        <div class="sm:col-span-4">
            <label class="mb-1 block text-xs font-medium text-slate-500">Pemicu Pembayaran</label>
            <input wire:model="trigger" type="text" placeholder="mis. Penyerahan Laporan Pendahuluan" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
        </div>

        <div class="sm:col-span-6">
            <label class="mb-1 block text-xs font-medium text-slate-500">Dokumen yang Diperlukan</label>
            <input wire:model="required_items" type="text" placeholder="mis. Invoice, Kwitansi, Faktur Pajak" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
        </div>

        <div class="flex items-end sm:col-span-3">
            <label class="flex items-center gap-2 text-xs text-slate-600">
                <input wire:model="override_limit" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                Izinkan melebihi sisa nilai kontrak
            </label>
        </div>

        @error('amount')
            <div class="sm:col-span-6 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                {{ $message }}
            </div>
        @enderror

        <div class="flex items-end gap-2 sm:col-span-3">
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                {{ $editing ? 'Simpan Perubahan' : '+ Tambah Termin' }}
            </button>
            @if ($editing)
                <button type="button" wire:click="cancelEdit" class="rounded-md border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                    Batal
                </button>
            @endif
        </div>
    </form>

    <div class="space-y-3">
        @forelse ($payments as $payment)
            <div wire:key="payment-{{ $payment->id }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $payment->name }}</p>
                        <p class="text-xs text-slate-500">
                            Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}
                            @if ($payment->percentage) ({{ rtrim(rtrim($payment->percentage, '0'), '.') }}%) @endif
                            @if ($payment->target_date) &middot; Target {{ $payment->target_date->translatedFormat('d M Y') }} @endif
                        </p>
                        @if ($payment->trigger)
                            <p class="mt-1 text-xs text-slate-400">Pemicu: {{ $payment->trigger }}</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <x-payment-status-badge :status="$payment->status" />
                        <button type="button" wire:click="edit('{{ $payment->id }}')" class="text-sm text-blue-600 hover:underline">
                            Ubah
                        </button>
                        <button
                            type="button"
                            wire:click="delete('{{ $payment->id }}')"
                            wire:confirm="Hapus termin \"{{ $payment->name }}\"?"
                            class="text-sm text-red-600 hover:underline"
                        >
                            Hapus
                        </button>
                    </div>
                </div>

                @if (count($this->availableTransitions($payment)))
                    <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                        <span class="text-xs font-medium uppercase tracking-wide text-slate-400">Ubah status ke:</span>
                        @foreach ($this->availableTransitions($payment) as $target)
                            <button
                                type="button"
                                wire:click="transitionTo('{{ $payment->id }}', '{{ $target->value }}')"
                                class="rounded-md border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50"
                            >
                                {{ $target->label() }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white p-8 text-center text-sm text-slate-400 shadow-sm">
                Belum ada termin.
            </div>
        @endforelse
    </div>
</div>
