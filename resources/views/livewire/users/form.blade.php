<div class="max-w-2xl">
    <div class="mb-4">
        <a href="{{ route('users.index') }}" wire:navigate class="text-sm text-slate-500 hover:text-slate-700">
            &larr; Kembali ke daftar pengguna
        </a>
    </div>

    <form wire:submit="save" class="space-y-5 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nama</label>
                <input wire:model="name" id="name" type="text" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                <input wire:model="email" id="email" type="email" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">
                    Password @if($user) <span class="font-normal text-slate-400">(kosongkan jika tidak diubah)</span> @endif
                </label>
                <input wire:model="password" id="password" type="password" autocomplete="new-password" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Konfirmasi Password</label>
                <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>
        </div>

        @if ($canChooseOrganization)
            <div>
                <label for="organization_id" class="mb-1 block text-sm font-medium text-slate-700">Organisasi</label>
                <select wire:model="organization_id" id="organization_id" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">— Tidak terikat organisasi (mis. Super Admin) —</option>
                    @foreach ($organizationOptions as $organization)
                        <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                    @endforeach
                </select>
                @error('organization_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Role</label>
            <div class="space-y-2 rounded-md border border-slate-200 p-3">
                @foreach ($assignableRoles as $role)
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="selectedRoles" value="{{ $role->name }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        {{ \App\Domain\Identity\Enums\RoleName::tryFrom($role->name)?->label() ?? $role->name }}
                    </label>
                @endforeach
            </div>
            @error('selectedRoles') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input wire:model="is_active" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
            Akun aktif
        </label>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
            <a href="{{ route('users.index') }}" wire:navigate class="rounded-md border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Batal
            </a>
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Simpan
            </button>
        </div>
    </form>
</div>
