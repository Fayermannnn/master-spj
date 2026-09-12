<div class="mx-auto max-w-2xl space-y-6">
    <h1 class="text-lg font-semibold text-slate-900">Kop Surat</h1>
    <p class="text-sm text-slate-500">
        Logo organisasi <strong>{{ $organization->name }}</strong> — otomatis dimasukkan ke placeholder
        <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">organization.logo</code> di setiap dokumen yang digenerate,
        tanpa perlu ditempel manual ke tiap template.
    </p>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        @if ($status)
            <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ $status }}
            </div>
        @endif

        @if ($organization->hasLogo())
            <div class="mb-4 flex items-center gap-4 rounded-md border border-slate-200 bg-slate-50 p-4">
                <img src="{{ route('organizations.logo', $organization) }}" alt="Logo {{ $organization->name }}" class="h-16 w-auto object-contain">
                <div class="text-sm">
                    <p class="font-medium text-slate-700">{{ $organization->logo_original_filename }}</p>
                    <button type="button" wire:click="remove" wire:confirm="Hapus logo ini?" class="text-xs text-red-600 hover:underline">
                        Hapus Logo
                    </button>
                </div>
            </div>
        @endif

        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">
                    {{ $organization->hasLogo() ? 'Ganti Logo' : 'Unggah Logo' }} (JPG/PNG, maks 2MB)
                </label>
                <input type="file" wire:model="logo" accept=".jpg,.jpeg,.png" class="block w-full text-sm">
                @error('logo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <div wire:loading wire:target="logo" class="mt-1 text-xs text-slate-400">Mengunggah…</div>
            </div>

            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Simpan Logo
            </button>
        </form>
    </div>
</div>
