<div class="space-y-5">
    <div class="mb-2">
        <a href="{{ route('projects.index') }}" wire:navigate class="text-sm text-slate-500 hover:text-slate-700">
            &larr; Kembali ke daftar project
        </a>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="font-mono text-xs text-slate-400">{{ $project->code }}</p>
                <h1 class="text-lg font-semibold text-slate-900">{{ $project->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $project->projectType->name }} · {{ $project->client->name }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <x-project-status-badge :status="$project->status" />
                @can('update', $project)
                    <a href="{{ route('projects.edit', $project) }}" wire:navigate class="rounded-md border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                        Ubah
                    </a>
                @endcan
            </div>
        </div>

        @can('transitionStatus', $project)
            @if (count($this->availableTransitions()))
                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                    <span class="text-xs font-medium uppercase tracking-wide text-slate-400">Ubah status ke:</span>
                    @foreach ($this->availableTransitions() as $target)
                        <button
                            type="button"
                            wire:click="transitionTo('{{ $target->value }}')"
                            wire:confirm="Ubah status project menjadi \"{{ $target->label() }}\"?"
                            class="rounded-md border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50"
                        >
                            {{ $target->label() }}
                        </button>
                    @endforeach
                </div>
            @endif
            @error('status')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        @endcan
    </div>

    <div class="border-b border-slate-200">
        <nav class="-mb-px flex gap-6 text-sm">
            <button
                type="button"
                wire:click="setTab('overview')"
                class="border-b-2 px-1 py-2 font-medium {{ $activeTab === 'overview' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
            >
                Overview
            </button>
            <button
                type="button"
                wire:click="setTab('contract')"
                class="border-b-2 px-1 py-2 font-medium {{ $activeTab === 'contract' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
            >
                Kontrak
            </button>
            <button
                type="button"
                wire:click="setTab('personnel')"
                class="border-b-2 px-1 py-2 font-medium {{ $activeTab === 'personnel' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
            >
                Personel
            </button>
            <button
                type="button"
                wire:click="setTab('cost')"
                class="border-b-2 px-1 py-2 font-medium {{ $activeTab === 'cost' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
            >
                Biaya
            </button>
            <button
                type="button"
                wire:click="setTab('payments')"
                class="border-b-2 px-1 py-2 font-medium {{ $activeTab === 'payments' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
            >
                Termin
            </button>
        </nav>
    </div>

    @if ($activeTab === 'overview')
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Informasi Umum</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Unit Kerja</dt><dd class="text-slate-900">{{ $project->unit_work ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Ketua Tim / PM</dt><dd class="text-slate-900">{{ $project->projectManagerPersonnel?->name ?? $project->project_manager_name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Tanggal Mulai</dt><dd class="text-slate-900">{{ $project->start_date?->translatedFormat('d M Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Tanggal Selesai</dt><dd class="text-slate-900">{{ $project->end_date?->translatedFormat('d M Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Organisasi (Provider)</dt><dd class="text-slate-900">{{ $project->organization->name }}</dd></div>
                </dl>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Klien & PPK</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Klien / Instansi</dt><dd class="text-slate-900">{{ $project->client->name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">PPK / Kontak</dt><dd class="text-slate-900">{{ $project->ppkContact?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Jabatan</dt><dd class="text-slate-900">{{ $project->ppkContact?->position ?? '—' }}</dd></div>
                </dl>
            </div>

            @if ($project->description)
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2">
                    <h3 class="mb-2 text-sm font-semibold text-slate-900">Deskripsi</h3>
                    <p class="whitespace-pre-line text-sm text-slate-700">{{ $project->description }}</p>
                </div>
            @endif
        </div>
    @elseif ($activeTab === 'contract')
        <livewire:contracts.form :project="$project" :key="'contract-'.$project->id" />
    @elseif ($activeTab === 'personnel')
        <livewire:project-personnel.manager :project="$project" :key="'personnel-'.$project->id" />
    @elseif ($activeTab === 'cost')
        <livewire:project-cost.manager :project="$project" :key="'cost-'.$project->id" />
    @else
        <livewire:project-payments.manager :project="$project" :key="'payments-'.$project->id" />
    @endif
</div>
