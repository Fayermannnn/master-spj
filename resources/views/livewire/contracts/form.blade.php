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
                <input wire:model="contract_value" id="contract_value" type="number" step="0.01" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('contract_value') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

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
</div>
