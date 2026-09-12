<div class="max-w-2xl">
    <div class="mb-4">
        <a href="{{ route('template-variables.index') }}" wire:navigate class="text-sm text-slate-500 hover:text-slate-700">
            &larr; Kembali ke daftar variabel
        </a>
    </div>

    <form wire:submit="save" class="space-y-5 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="key" class="mb-1 block text-sm font-medium text-slate-700">Key</label>
                <input wire:model="key" id="key" type="text" placeholder="mis. project.name" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm font-mono shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('key') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="label" class="mb-1 block text-sm font-medium text-slate-700">Label</label>
                <input wire:model="label" id="label" type="text" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="data_type" class="mb-1 block text-sm font-medium text-slate-700">Tipe Data</label>
            <select wire:model="data_type" id="data_type" class="block w-full max-w-xs rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @foreach ($dataTypeOptions as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="description" class="mb-1 block text-sm font-medium text-slate-700">Deskripsi</label>
            <textarea wire:model="description" id="description" rows="2" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input wire:model="is_active" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
            Variabel aktif
        </label>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
            <a href="{{ route('template-variables.index') }}" wire:navigate class="rounded-md border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Batal
            </a>
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Simpan
            </button>
        </div>
    </form>
</div>
