<div class="space-y-4">
    <form wire:submit="save" class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-6">
        <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-500">Kategori</label>
            <select wire:model="cost_category_id" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                <option value="">— Pilih kategori —</option>
                @foreach ($categoryOptions as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            @error('cost_category_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-4">
            <label class="mb-1 block text-xs font-medium text-slate-500">Deskripsi</label>
            <input wire:model="description" type="text" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
            @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Jumlah</label>
            <input wire:model="quantity" type="number" step="0.01" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
            @error('quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Satuan</label>
            <input wire:model="unit" type="text" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
        </div>

        <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-500">Harga Satuan (Rp)</label>
            <input wire:model="unit_price" type="number" step="0.01" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
            @error('unit_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-500">Jenis Pajak</label>
            <select wire:model="tax_type_id" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                <option value="">— Tidak kena pajak —</option>
                @foreach ($taxTypeOptions as $tax)
                    <option value="{{ $tax->id }}">{{ $tax->name }} ({{ rtrim(rtrim($tax->rate, '0'), '.') }}%)</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-end sm:col-span-2">
            <label class="flex items-center gap-2 text-xs text-slate-600">
                <input wire:model="is_tax_inclusive" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                Harga sudah termasuk pajak
            </label>
        </div>

        <div class="flex items-end gap-2 sm:col-span-2">
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                {{ $editing ? 'Simpan Perubahan' : '+ Tambah Item' }}
            </button>
            @if ($editing)
                <button type="button" wire:click="cancelEdit" class="rounded-md border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                    Batal
                </button>
            @endif
        </div>
    </form>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Kategori</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Deskripsi</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Subtotal</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Pajak</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Total</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($costItems as $item)
                    <tr wire:key="cost-{{ $item->id }}">
                        <td class="px-4 py-3 text-slate-500">{{ $item->category->name }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">
                            {{ $item->description }}
                            <span class="block text-xs font-normal text-slate-400">
                                {{ rtrim(rtrim($item->quantity, '0'), '.') }} {{ $item->unit }} &times; Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-slate-500">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-slate-500">
                            {{ $item->taxType?->name ?? '—' }}
                            @if ($item->tax_amount > 0)
                                <span class="block text-xs">Rp {{ number_format((float) $item->tax_amount, 0, ',', '.') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-slate-900">Rp {{ number_format((float) $item->total, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <button type="button" wire:click="edit('{{ $item->id }}')" class="text-blue-600 hover:underline">
                                    Ubah
                                </button>
                                <button
                                    type="button"
                                    wire:click="delete('{{ $item->id }}')"
                                    wire:confirm="Hapus item biaya \"{{ $item->description }}\"?"
                                    class="text-red-600 hover:underline"
                                >
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada item biaya.</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($costItems->isNotEmpty())
                <tfoot class="bg-slate-50">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-slate-700">Total Keseluruhan</td>
                        <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">
                            Rp {{ number_format((float) $costItems->sum('total'), 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
