<div class="space-y-6">
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">
            Selamat datang, {{ auth()->user()->name }}
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Ringkasan project, kontrak, dan milestone terdekat.
            <a href="{{ route('reports.project-summary') }}" class="text-blue-600 hover:underline">Lihat laporan lengkap →</a>
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Project</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $totalProjects }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Project Aktif</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $activeProjects }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Nilai Kontrak</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">Rp {{ number_format($totalContractValue, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Project per Status</h3>
            <dl class="space-y-2 text-sm">
                @foreach ($statuses as $status)
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ $status->label() }}</dt>
                        <dd class="font-medium text-slate-900">{{ $projectsByStatus[$status->value] ?? 0 }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Milestone Terdekat</h3>
            <div class="space-y-3">
                @forelse ($upcomingMilestones as $milestone)
                    <div class="flex items-center justify-between text-sm">
                        <div>
                            <a href="{{ route('projects.show', $milestone->project) }}" wire:navigate class="font-medium text-slate-900 hover:text-blue-600">{{ $milestone->name }}</a>
                            <p class="text-xs text-slate-400">{{ $milestone->project->name }}</p>
                        </div>
                        <span class="text-xs {{ $milestone->isOverdue() ? 'font-medium text-red-600' : 'text-slate-500' }}">
                            {{ $milestone->target_date->translatedFormat('d M Y') }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Tidak ada milestone yang akan datang.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
