<div class="space-y-4">
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="mb-4 text-sm font-semibold text-slate-900">Garis Waktu Project</h3>

        @if ($project->start_date && $project->end_date)
            <div class="relative mb-2 h-2 rounded-full bg-slate-100">
                @if ($todayPosition !== null)
                    <div class="absolute top-0 h-2 w-0.5 bg-slate-400" style="left: {{ $todayPosition }}%" title="Hari ini"></div>
                @endif
                @foreach ($milestones as $milestone)
                    @php $position = $this->timelinePosition($milestone->target_date); @endphp
                    @if ($position !== null)
                        <div
                            class="absolute -top-1 h-4 w-4 -translate-x-1/2 rounded-full border-2 border-white shadow {{ $milestone->status->value === 'completed' ? 'bg-emerald-500' : ($milestone->isOverdue() ? 'bg-red-500' : 'bg-blue-500') }}"
                            style="left: {{ $position }}%"
                            title="{{ $milestone->name }} — {{ $milestone->target_date->translatedFormat('d M Y') }}"
                        ></div>
                    @endif
                @endforeach
            </div>
            <div class="flex justify-between text-xs text-slate-400">
                <span>{{ $project->start_date->translatedFormat('d M Y') }}</span>
                <span>{{ $project->end_date->translatedFormat('d M Y') }}</span>
            </div>
        @else
            <p class="text-sm text-slate-400">Isi tanggal mulai & selesai project (tab Overview) untuk menampilkan garis waktu visual.</p>
        @endif
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold text-slate-900">Milestone</h3>

        @can('update', $project)
            <form wire:submit="saveMilestone" class="mb-4 grid grid-cols-1 gap-3 rounded-md border border-slate-100 bg-slate-50 p-4 sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-slate-500">Nama Milestone</label>
                    <input type="text" wire:model="milestoneName" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    @error('milestoneName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Tanggal Target</label>
                    <input type="date" wire:model="milestoneTargetDate" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    @error('milestoneTargetDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        {{ $editingMilestoneId ? 'Simpan' : 'Tambah' }}
                    </button>
                    @if ($editingMilestoneId)
                        <button type="button" wire:click="cancelMilestoneEdit" class="rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">Batal</button>
                    @endif
                </div>
                <div class="sm:col-span-4">
                    <label class="mb-1 block text-xs font-medium text-slate-500">Catatan</label>
                    <input type="text" wire:model="milestoneNotes" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                </div>
            </form>
        @endcan

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr>
                        <th class="px-2 py-2 text-left font-medium text-slate-500">Nama</th>
                        <th class="px-2 py-2 text-left font-medium text-slate-500">Target</th>
                        <th class="px-2 py-2 text-left font-medium text-slate-500">Status</th>
                        <th class="px-2 py-2 text-right font-medium text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($milestones as $milestone)
                        <tr wire:key="milestone-{{ $milestone->id }}">
                            <td class="px-2 py-2 font-medium text-slate-900">{{ $milestone->name }}</td>
                            <td class="px-2 py-2 text-slate-500">
                                {{ $milestone->target_date->translatedFormat('d M Y') }}
                                @if ($milestone->isOverdue())
                                    <span class="ml-1 text-xs font-medium text-red-600">Terlambat</span>
                                @endif
                            </td>
                            <td class="px-2 py-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $milestone->status->value === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($milestone->status->value === 'in_progress' ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600') }}">
                                    {{ $milestone->status->label() }}
                                </span>
                            </td>
                            <td class="px-2 py-2 text-right">
                                @can('update', $project)
                                    <div class="flex justify-end gap-3">
                                        @foreach ($statuses as $status)
                                            @if ($status !== $milestone->status)
                                                <button type="button" wire:click="setMilestoneStatus('{{ $milestone->id }}', '{{ $status->value }}')" class="text-slate-500 hover:underline">
                                                    {{ $status->label() }}
                                                </button>
                                            @endif
                                        @endforeach
                                        <button type="button" wire:click="editMilestone('{{ $milestone->id }}')" class="text-blue-600 hover:underline">Ubah</button>
                                        <button type="button" wire:click="deleteMilestone('{{ $milestone->id }}')" wire:confirm="Hapus milestone \"{{ $milestone->name }}\"?" class="text-red-600 hover:underline">Hapus</button>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-2 py-6 text-center text-slate-400">Belum ada milestone.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold text-slate-900">Deliverable / Output</h3>

        @can('update', $project)
            <form wire:submit="saveDeliverable" class="mb-4 grid grid-cols-1 gap-3 rounded-md border border-slate-100 bg-slate-50 p-4 sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-slate-500">Nama Deliverable</label>
                    <input type="text" wire:model="deliverableName" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    @error('deliverableName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Tanggal Target</label>
                    <input type="date" wire:model="deliverableTargetDate" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Terkait Milestone</label>
                    <select wire:model="deliverableMilestoneId" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                        <option value="">—</option>
                        @foreach ($milestones as $milestone)
                            <option value="{{ $milestone->id }}">{{ $milestone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-3">
                    <label class="mb-1 block text-xs font-medium text-slate-500">Catatan</label>
                    <input type="text" wire:model="deliverableNotes" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        {{ $editingDeliverableId ? 'Simpan' : 'Tambah' }}
                    </button>
                    @if ($editingDeliverableId)
                        <button type="button" wire:click="cancelDeliverableEdit" class="rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">Batal</button>
                    @endif
                </div>
            </form>
        @endcan

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr>
                        <th class="px-2 py-2 text-left font-medium text-slate-500">Nama</th>
                        <th class="px-2 py-2 text-left font-medium text-slate-500">Milestone</th>
                        <th class="px-2 py-2 text-left font-medium text-slate-500">Target</th>
                        <th class="px-2 py-2 text-left font-medium text-slate-500">Status</th>
                        <th class="px-2 py-2 text-right font-medium text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($deliverables as $deliverable)
                        <tr wire:key="deliverable-{{ $deliverable->id }}">
                            <td class="px-2 py-2 font-medium text-slate-900">{{ $deliverable->name }}</td>
                            <td class="px-2 py-2 text-slate-500">{{ $deliverable->milestone?->name ?? '—' }}</td>
                            <td class="px-2 py-2 text-slate-500">
                                {{ $deliverable->target_date?->translatedFormat('d M Y') ?? '—' }}
                                @if ($deliverable->isOverdue())
                                    <span class="ml-1 text-xs font-medium text-red-600">Terlambat</span>
                                @endif
                            </td>
                            <td class="px-2 py-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $deliverable->status->value === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($deliverable->status->value === 'in_progress' ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600') }}">
                                    {{ $deliverable->status->label() }}
                                </span>
                            </td>
                            <td class="px-2 py-2 text-right">
                                @can('update', $project)
                                    <div class="flex justify-end gap-3">
                                        @foreach ($statuses as $status)
                                            @if ($status !== $deliverable->status)
                                                <button type="button" wire:click="setDeliverableStatus('{{ $deliverable->id }}', '{{ $status->value }}')" class="text-slate-500 hover:underline">
                                                    {{ $status->label() }}
                                                </button>
                                            @endif
                                        @endforeach
                                        <button type="button" wire:click="editDeliverable('{{ $deliverable->id }}')" class="text-blue-600 hover:underline">Ubah</button>
                                        <button type="button" wire:click="deleteDeliverable('{{ $deliverable->id }}')" wire:confirm="Hapus deliverable \"{{ $deliverable->name }}\"?" class="text-red-600 hover:underline">Hapus</button>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-2 py-6 text-center text-slate-400">Belum ada deliverable.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
