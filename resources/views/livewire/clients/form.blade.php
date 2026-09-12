<div class="max-w-3xl">
    <div class="mb-4">
        <a href="{{ route('clients.index') }}" wire:navigate class="text-sm text-slate-500 hover:text-slate-700">
            &larr; Kembali ke daftar klien
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="space-y-5 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Data Klien / Instansi</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nama Instansi</label>
                    <input wire:model="name" id="name" type="text" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Telepon</label>
                    <input wire:model="phone" id="phone" type="text" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input wire:model="email" id="email" type="email" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="address" class="mb-1 block text-sm font-medium text-slate-700">Alamat</label>
                <textarea wire:model="address" id="address" rows="2" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($canChooseOrganization)
                <div>
                    <label for="organization_id" class="mb-1 block text-sm font-medium text-slate-700">Organisasi (Provider)</label>
                    <select wire:model="organization_id" id="organization_id" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        @foreach ($organizationOptions as $organization)
                            <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                        @endforeach
                    </select>
                    @error('organization_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="is_active" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                Klien aktif
            </label>
        </div>

        <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Kontak (PPK / PPTK / PA-KPA)</h2>
                <button type="button" wire:click="addContact" class="text-sm font-medium text-blue-600 hover:underline">
                    + Tambah Kontak
                </button>
            </div>

            @foreach ($contacts as $index => $contact)
                <div wire:key="contact-{{ $index }}" class="grid grid-cols-1 gap-3 rounded-md border border-slate-100 bg-slate-50 p-4 sm:grid-cols-12">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-slate-500">Jenis</label>
                        <select wire:model="contacts.{{ $index }}.type" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                            @foreach ($contactTypes as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-slate-500">Nama</label>
                        <input wire:model="contacts.{{ $index }}.name" type="text" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                        @error("contacts.{$index}.name") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-slate-500">Jabatan</label>
                        <input wire:model="contacts.{{ $index }}.position" type="text" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-slate-500">Telepon</label>
                        <input wire:model="contacts.{{ $index }}.phone" type="text" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    </div>
                    <div class="sm:col-span-1 flex items-end justify-end">
                        <button type="button" wire:click="removeContact({{ $index }})" class="text-sm text-red-600 hover:underline">
                            Hapus
                        </button>
                    </div>
                    <div class="sm:col-span-11">
                        <label class="mb-1 block text-xs font-medium text-slate-500">Email</label>
                        <input wire:model="contacts.{{ $index }}.email" type="email" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                        @error("contacts.{$index}.email") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('clients.index') }}" wire:navigate class="rounded-md border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Batal
            </a>
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Simpan
            </button>
        </div>
    </form>
</div>
