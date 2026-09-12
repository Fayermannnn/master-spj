<div class="space-y-6">
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">
            Selamat datang, {{ auth()->user()->name }}
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Modul Project, SPJ, dan laporan akan tersedia di fase pengembangan berikutnya.
            Saat ini Anda dapat mengelola
            @can('organizations.viewAny')
                <a href="{{ route('organizations.index') }}" class="text-blue-600 hover:underline">organisasi</a>
                dan
            @endcan
            <a href="{{ route('users.index') }}" class="text-blue-600 hover:underline">pengguna</a>.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Organisasi</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ \App\Models\Organization::query()->count() }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Pengguna</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ \App\Models\User::query()->count() }}</p>
        </div>
    </div>
</div>
