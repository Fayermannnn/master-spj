<div class="space-y-5">
    <div class="mb-2">
        <a href="{{ route('personnel.index') }}" wire:navigate class="text-sm text-slate-500 hover:text-slate-700">
            &larr; Kembali ke daftar personel
        </a>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ $personnel->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $personnel->category->name }} · {{ $personnel->position ?? '—' }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <x-status-badge :active="$personnel->is_active" />
                @can('update', $personnel)
                    <a href="{{ route('personnel.edit', $personnel) }}" wire:navigate class="rounded-md border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                        Ubah
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="overflow-x-auto border-b border-slate-200">
        <nav class="-mb-px flex w-max min-w-full gap-6 text-sm">
            <button
                type="button"
                wire:click="setTab('overview')"
                class="border-b-2 px-1 py-2 font-medium {{ $activeTab === 'overview' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
            >
                Overview
            </button>
            <button
                type="button"
                wire:click="setTab('documents')"
                class="border-b-2 px-1 py-2 font-medium {{ $activeTab === 'documents' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
            >
                Dokumen
            </button>
        </nav>
    </div>

    @if ($activeTab === 'overview')
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Data Pribadi</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Pendidikan</dt><dd class="text-slate-900">{{ $personnel->education ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">No. KTP</dt><dd class="text-slate-900">{{ $personnel->id_number ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">NPWP</dt><dd class="text-slate-900">{{ $personnel->npwp ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Telepon</dt><dd class="text-slate-900">{{ $personnel->phone ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd class="text-slate-900">{{ $personnel->email ?? '—' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Sertifikasi & Tarif</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">No. SKA/SKK</dt><dd class="text-slate-900">{{ $personnel->certificate_number ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Masa Berlaku</dt><dd class="text-slate-900">{{ $personnel->certificate_expiry_date?->translatedFormat('d M Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Tarif Default</dt><dd class="text-slate-900">{{ $personnel->default_rate ? 'Rp '.number_format((float) $personnel->default_rate, 0, ',', '.') : '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Organisasi</dt><dd class="text-slate-900">{{ $personnel->organization->name }}</dd></div>
                </dl>
            </div>

            @if ($personnel->expertise)
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2">
                    <h3 class="mb-2 text-sm font-semibold text-slate-900">Keahlian</h3>
                    <p class="whitespace-pre-line text-sm text-slate-700">{{ $personnel->expertise }}</p>
                </div>
            @endif
        </div>
    @else
        <livewire:personnel.documents :personnel="$personnel" :key="'personnel-docs-'.$personnel->id" />
    @endif
</div>
