<div class="mx-auto max-w-2xl space-y-6">
    <h1 class="text-lg font-semibold text-slate-900">Profil Saya</h1>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-sm font-semibold text-slate-900">Informasi Profil</h2>

        @if ($profileStatus)
            <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ $profileStatus }}
            </div>
        @endif

        <form wire:submit="updateProfile" class="space-y-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Nama</label>
                <input type="text" wire:model="name" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Email</label>
                <input type="email" wire:model="email" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Simpan Profil
            </button>
        </form>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-sm font-semibold text-slate-900">Ubah Password</h2>

        @if ($passwordStatus)
            <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ $passwordStatus }}
            </div>
        @endif

        <form wire:submit="updatePassword" class="space-y-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Password Saat Ini</label>
                <input type="password" wire:model="current_password" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                @error('current_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Password Baru</label>
                <input type="password" wire:model="password" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Konfirmasi Password Baru</label>
                <input type="password" wire:model="password_confirmation" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
            </div>

            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Ubah Password
            </button>
        </form>
    </div>
</div>
