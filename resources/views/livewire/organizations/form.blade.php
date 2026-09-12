<div class="max-w-2xl">
    <div class="mb-4">
        <a href="{{ route('organizations.index') }}" wire:navigate class="text-sm text-slate-500 hover:text-slate-700">
            &larr; Kembali ke daftar organisasi
        </a>
    </div>

    <form wire:submit="save" class="space-y-5 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="code" class="mb-1 block text-sm font-medium text-slate-700">Kode</label>
                <input wire:model="code" id="code" type="text" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nama Organisasi</label>
                <input wire:model="name" id="name" type="text" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="npwp" class="mb-1 block text-sm font-medium text-slate-700">NPWP</label>
                <input wire:model="npwp" id="npwp" type="text" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('npwp') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Telepon</label>
                <input wire:model="phone" id="phone" type="text" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
            <input wire:model="email" id="email" type="email" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="address" class="mb-1 block text-sm font-medium text-slate-700">Alamat</label>
            <textarea wire:model="address" id="address" rows="3" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
            @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input wire:model="is_active" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
            Organisasi aktif
        </label>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
            <a href="{{ route('organizations.index') }}" wire:navigate class="rounded-md border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Batal
            </a>
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Simpan
            </button>
        </div>
    </form>
</div>
