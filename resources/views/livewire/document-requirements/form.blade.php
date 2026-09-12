<div class="max-w-3xl">
    <div class="mb-4">
        <a href="{{ route('document-requirements.index') }}" wire:navigate class="text-sm text-slate-500 hover:text-slate-700">
            &larr; Kembali ke daftar kebutuhan dokumen
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="space-y-5 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="code" class="mb-1 block text-sm font-medium text-slate-700">Kode</label>
                    <input wire:model="code" id="code" type="text" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nama</label>
                    <input wire:model="name" id="name" type="text" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="project_type_id" class="mb-1 block text-sm font-medium text-slate-700">Jenis Project</label>
                    <select wire:model="project_type_id" id="project_type_id" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">— Semua Jenis Project —</option>
                        @foreach ($projectTypeOptions as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="category" class="mb-1 block text-sm font-medium text-slate-700">Kategori</label>
                    <input wire:model="category" id="category" type="text" placeholder="mis. Administrasi, Keuangan" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label for="sort_order" class="mb-1 block text-sm font-medium text-slate-700">Urutan Tampil</label>
                    <input wire:model="sort_order" id="sort_order" type="number" min="0" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    @error('sort_order') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="description" class="mb-1 block text-sm font-medium text-slate-700">Deskripsi</label>
                <textarea wire:model="description" id="description" rows="2" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="is_active" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                Kebutuhan dokumen aktif
            </label>
        </div>

        <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Kondisi (Rule)</h2>
                    <p class="text-xs text-slate-400">Kosongkan jika dokumen ini selalu wajib. Semua kondisi aktif digabung dengan AND.</p>
                </div>
                <button type="button" wire:click="addRule" class="text-sm font-medium text-blue-600 hover:underline">
                    + Tambah Kondisi
                </button>
            </div>

            @foreach ($rules as $index => $rule)
                <div wire:key="rule-{{ $index }}" class="grid grid-cols-1 gap-3 rounded-md border border-slate-100 bg-slate-50 p-4 sm:grid-cols-12">
                    <div class="sm:col-span-5">
                        <label class="mb-1 block text-xs font-medium text-slate-500">Field</label>
                        <select wire:model="rules.{{ $index }}.field" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                            @foreach ($fieldOptions as $field)
                                <option value="{{ $field->value }}">{{ $field->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-slate-500">Operator</label>
                        <select wire:model="rules.{{ $index }}.operator" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                            @foreach ($operatorOptions as $operator)
                                <option value="{{ $operator->value }}">{{ $operator->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-slate-500">Nilai</label>
                        <input wire:model="rules.{{ $index }}.value" type="text" placeholder="mis. TENAGA_AHLI" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    </div>

                    <div class="flex items-end justify-end sm:col-span-1">
                        <button type="button" wire:click="removeRule({{ $index }})" class="text-sm text-red-600 hover:underline">
                            Hapus
                        </button>
                    </div>
                </div>
            @endforeach

            @if (empty($rules))
                <p class="text-sm text-slate-400">Belum ada kondisi — dokumen ini selalu wajib untuk jenis project di atas.</p>
            @endif
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('document-requirements.index') }}" wire:navigate class="rounded-md border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Batal
            </a>
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Simpan
            </button>
        </div>
    </form>
</div>
