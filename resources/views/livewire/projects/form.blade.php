<div class="max-w-3xl">
    <div class="mb-4">
        <a href="{{ route('projects.index') }}" wire:navigate class="text-sm text-slate-500 hover:text-slate-700">
            &larr; Kembali ke daftar project
        </a>
    </div>

    <form wire:submit="save" class="space-y-5 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="code" class="mb-1 block text-sm font-medium text-slate-700">Kode Project</label>
                <input wire:model="code" id="code" type="text" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nama Project</label>
                <input wire:model="name" id="name" type="text" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        @if ($canChooseOrganization)
            <div>
                <label for="organization_id" class="mb-1 block text-sm font-medium text-slate-700">Organisasi (Provider)</label>
                <select wire:model.live="organization_id" id="organization_id" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">— Pilih organisasi —</option>
                    @foreach ($organizationOptions as $organization)
                        <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                    @endforeach
                </select>
                @error('organization_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="project_type_id" class="mb-1 block text-sm font-medium text-slate-700">Jenis Project</label>
                <select wire:model="project_type_id" id="project_type_id" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">— Pilih jenis project —</option>
                    @foreach ($projectTypeOptions as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
                @error('project_type_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="client_id" class="mb-1 block text-sm font-medium text-slate-700">Klien / Instansi</label>
                <select wire:model.live="client_id" id="client_id" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">— Pilih klien —</option>
                    @foreach ($clientOptions as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
                @error('client_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @if (! $canChooseOrganization && $clientOptions->isEmpty())
                    <p class="mt-1 text-xs text-amber-600">Belum ada klien untuk organisasi Anda — buat klien terlebih dahulu.</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="ppk_contact_id" class="mb-1 block text-sm font-medium text-slate-700">PPK / Kontak</label>
                <select wire:model="ppk_contact_id" id="ppk_contact_id" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">— Tidak ditentukan —</option>
                    @foreach ($contactOptions as $contact)
                        <option value="{{ $contact->id }}">{{ $contact->name }} ({{ $contact->type->label() }})</option>
                    @endforeach
                </select>
                @error('ppk_contact_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="unit_work" class="mb-1 block text-sm font-medium text-slate-700">Unit Kerja</label>
                <input wire:model="unit_work" id="unit_work" type="text" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('unit_work') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="project_manager_personnel_id" class="mb-1 block text-sm font-medium text-slate-700">Ketua Tim / PM (dari roster Personnel)</label>
                <select wire:model="project_manager_personnel_id" id="project_manager_personnel_id" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">— Belum ditentukan / belum tercatat sebagai Personnel —</option>
                    @foreach ($personnelOptions as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
                @error('project_manager_personnel_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="project_manager_name" class="mb-1 block text-sm font-medium text-slate-700">
                    Nama PM <span class="font-normal text-slate-400">(kalau belum tercatat di roster Personnel)</span>
                </label>
                <input wire:model="project_manager_name" id="project_manager_name" type="text" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('project_manager_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="start_date" class="mb-1 block text-sm font-medium text-slate-700">Tanggal Mulai</label>
                <input wire:model="start_date" id="start_date" type="date" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('start_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="end_date" class="mb-1 block text-sm font-medium text-slate-700">Tanggal Selesai</label>
                <input wire:model="end_date" id="end_date" type="date" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('end_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="description" class="mb-1 block text-sm font-medium text-slate-700">Deskripsi</label>
            <textarea wire:model="description" id="description" rows="3" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Catatan</label>
            <textarea wire:model="notes" id="notes" rows="2" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
            @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
            <a href="{{ route('projects.index') }}" wire:navigate class="rounded-md border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Batal
            </a>
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Simpan
            </button>
        </div>
    </form>
</div>
