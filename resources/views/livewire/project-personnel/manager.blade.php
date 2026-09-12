<div class="space-y-4">
    <form wire:submit="save" class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-6">
        <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-500">Personel</label>
            <select wire:model.live="personnel_id" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm" @if($editing) disabled @endif>
                <option value="">— Pilih personel —</option>
                @foreach ($personnelOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                @endforeach
            </select>
            @error('personnel_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-500">Peran di Project</label>
            <input wire:model="role_on_project" type="text" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Jumlah</label>
            <input wire:model="quantity" type="number" step="0.01" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
            @error('quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Satuan</label>
            <input wire:model="unit" type="text" list="unit-suggestions" placeholder="OB" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
            <datalist id="unit-suggestions">
                <option value="OB">OB</option>
                <option value="OH">OH</option>
                <option value="OM">OM</option>
                <option value="LS">LS</option>
                <option value="Hari">Hari</option>
                <option value="Jam">Jam</option>
                <option value="HM">HM</option>
            </datalist>
        </div>

        <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-500">Harga Satuan (Rp)</label>
            <input wire:model="unit_price" type="number" step="0.01" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
            @error('unit_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Mulai</label>
            <input wire:model="start_date" type="date" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Selesai</label>
            <input wire:model="end_date" type="date" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
            @error('end_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-end gap-2 sm:col-span-2">
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                {{ $editing ? 'Simpan Perubahan' : '+ Tugaskan' }}
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
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Personel</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Peran</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Jumlah</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Harga Satuan</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Subtotal</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($assignments as $assignment)
                    <tr wire:key="assignment-{{ $assignment->id }}">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $assignment->personnel->name }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $assignment->role_on_project ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-slate-500">{{ rtrim(rtrim($assignment->quantity, '0'), '.') }} {{ $assignment->unit }}</td>
                        <td class="px-4 py-3 text-right text-slate-500">Rp {{ number_format((float) $assignment->unit_price, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-medium text-slate-900">Rp {{ number_format((float) $assignment->subtotal, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <button type="button" wire:click="edit('{{ $assignment->id }}')" class="text-blue-600 hover:underline">
                                    Ubah
                                </button>
                                <button
                                    type="button"
                                    wire:click="delete('{{ $assignment->id }}')"
                                    wire:confirm="Hapus penugasan \"{{ $assignment->personnel->name }}\"?"
                                    class="text-red-600 hover:underline"
                                >
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada personel yang ditugaskan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">Perjalanan Dinas</h3>
            @if (! $showTravelForm)
                <button type="button" wire:click="openTravelForm" class="text-sm text-blue-600 hover:underline">
                    + Tambah Perjalanan Dinas
                </button>
            @endif
        </div>

        @if ($showTravelForm)
            <form wire:submit="saveTravel" class="mt-4 grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Personel</label>
                    <select wire:model="travel_personnel_id" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                        <option value="">— Pilih personel —</option>
                        @foreach ($personnelOptions as $option)
                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                        @endforeach
                    </select>
                    @error('travel_personnel_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Tujuan</label>
                    <input wire:model="travel_destination" type="text" placeholder="mis. Jakarta" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    @error('travel_destination') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-slate-500">Keperluan</label>
                    <textarea wire:model="travel_purpose" rows="2" placeholder="mis. Koordinasi dengan PPK dan survey lapangan" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm"></textarea>
                    @error('travel_purpose') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Tanggal Berangkat</label>
                    <input wire:model="travel_departure_date" type="date" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    @error('travel_departure_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Tanggal Kembali</label>
                    <input wire:model="travel_return_date" type="date" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    @error('travel_return_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Moda Transportasi (opsional)</label>
                    <input wire:model="travel_transportation_mode" type="text" placeholder="mis. Udara" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                        {{ $editingTravel ? 'Simpan Perubahan' : 'Simpan' }}
                    </button>
                    <button type="button" wire:click="cancelTravelForm" class="rounded-md border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                </div>
            </form>
        @endif

        <div class="mt-4 space-y-2">
            @forelse ($travelAssignments as $travel)
                <div wire:key="travel-{{ $travel->id }}" class="flex items-start justify-between rounded-md border border-slate-100 bg-slate-50 p-3 text-sm">
                    <div>
                        <p class="font-medium text-slate-700">{{ $travel->personnel->name }} — {{ $travel->destination }}</p>
                        <p class="text-xs text-slate-500">{{ $travel->purpose }}</p>
                        <p class="text-xs text-slate-400">
                            {{ $travel->departure_date->translatedFormat('d M Y') }} &ndash; {{ $travel->return_date->translatedFormat('d M Y') }}
                            @if ($travel->transportation_mode) &middot; {{ $travel->transportation_mode }} @endif
                        </p>
                    </div>
                    <div class="flex gap-3 whitespace-nowrap text-xs">
                        <button type="button" wire:click="editTravel('{{ $travel->id }}')" class="text-blue-600 hover:underline">Ubah</button>
                        <button
                            type="button"
                            wire:click="deleteTravel('{{ $travel->id }}')"
                            wire:confirm="Hapus perjalanan dinas ini?"
                            class="text-red-600 hover:underline"
                        >
                            Hapus
                        </button>
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-400">Belum ada perjalanan dinas yang dicatat.</p>
            @endforelse
        </div>
    </div>
</div>
