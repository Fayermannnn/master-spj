<div class="space-y-4">
    @error('manifest')
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    @if ($selectedPackage === null)
        @can('update', $project)
            <form wire:submit="createPackage" class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-slate-500">Nama Paket</label>
                    <input type="text" wire:model="newPackageName" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm" placeholder="Mis. SPJ Termin 1">
                    @error('newPackageName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Cakupan</label>
                    <select wire:model="newPackagePaymentId" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                        <option value="">Level Project (SPJ Akhir)</option>
                        @foreach ($payments as $payment)
                            <option value="{{ $payment->id }}">Termin {{ $payment->termin_number }} — {{ $payment->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Buat Paket
                    </button>
                </div>
            </form>
        @endcan

        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-500">Nama</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-500">Cakupan</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-500">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($packages as $package)
                        <tr wire:key="package-{{ $package->id }}">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $package->name }}</td>
                            <td class="px-4 py-3 text-slate-500">
                                {{ $package->payment ? 'Termin '.$package->payment->termin_number : 'Level Project' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ match($package->status->value) { 'finalized' => 'bg-emerald-50 text-emerald-700', 'submitted' => 'bg-amber-50 text-amber-700', default => 'bg-slate-100 text-slate-600' } }}">
                                    {{ $package->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-3">
                                    <button type="button" wire:click="selectPackage('{{ $package->id }}')" class="text-blue-600 hover:underline">Kelola</button>
                                    <a href="{{ route('spj-packages.export', $package) }}" class="text-blue-600 hover:underline">Ekspor ZIP</a>
                                    @can('update', $project)
                                        <button type="button" wire:click="deletePackage('{{ $package->id }}')" wire:confirm="Hapus paket \"{{ $package->name }}\"?" class="text-red-600 hover:underline">
                                            Hapus
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-400">Belum ada paket SPJ.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <button type="button" wire:click="backToList" class="mb-2 text-xs text-slate-500 hover:text-slate-700">&larr; Kembali ke daftar paket</button>
                    <p class="text-sm font-semibold text-slate-900">{{ $selectedPackage->name }}</p>
                    <p class="text-xs text-slate-500">
                        {{ $selectedPackage->payment ? 'Termin '.$selectedPackage->payment->termin_number.' — '.$selectedPackage->payment->name : 'Level Project (SPJ Akhir)' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ match($selectedPackage->status->value) { 'finalized' => 'bg-emerald-50 text-emerald-700', 'submitted' => 'bg-amber-50 text-amber-700', default => 'bg-slate-100 text-slate-600' } }}">
                        {{ $selectedPackage->status->label() }}
                    </span>
                    <a href="{{ route('spj-packages.export', $selectedPackage) }}" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        Ekspor ZIP
                    </a>
                    @can('update', $project)
                        @if ($selectedPackage->status->value === 'draft')
                            <button type="button" wire:click="submit" wire:confirm="Ajukan paket ini untuk direview? Manifest tidak bisa diubah lagi selama menunggu review." class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                                Ajukan untuk Review
                            </button>
                        @endif
                    @endcan
                    @can('review', $selectedPackage)
                        @if ($selectedPackage->status->value === 'submitted')
                            <button type="button" wire:click="approve" wire:confirm="Setujui paket ini? Status akan menjadi Final dan tidak bisa diubah lagi." class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                                Setujui
                            </button>
                            @if (! $showRejectForm)
                                <button type="button" wire:click="openRejectForm" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">
                                    Tolak
                                </button>
                            @endif
                        @endif
                    @endcan
                </div>
            </div>

            @if ($selectedPackage->status->value === 'submitted')
                @can('review', $selectedPackage)
                    @if ($showRejectForm)
                        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4">
                            <label class="mb-1 block text-xs font-medium text-red-700">Alasan Penolakan (wajib diisi)</label>
                            <textarea wire:model="rejectNotes" rows="2" class="block w-full rounded-md border border-red-300 px-2 py-1.5 text-sm" placeholder="mis. Kwitansi Termin 2 belum sesuai format, mohon dilengkapi ulang."></textarea>
                            @error('rejectNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            <div class="mt-2 flex gap-2">
                                <button type="button" wire:click="reject" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">
                                    Kirim Penolakan
                                </button>
                                <button type="button" wire:click="cancelRejectForm" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                                    Batal
                                </button>
                            </div>
                        </div>
                    @endif
                @else
                    <p class="mt-4 text-xs text-slate-400">Menunggu direview oleh admin perusahaan.</p>
                @endcan
            @elseif ($selectedPackage->status->value === 'draft' && $selectedPackage->review_notes)
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                    <p class="font-medium">Ditolak{{ $selectedPackage->reviewedBy ? ' oleh '.$selectedPackage->reviewedBy->name : '' }}:</p>
                    <p class="mt-1">{{ $selectedPackage->review_notes }}</p>
                </div>
            @endif

            @if ($coverage !== null)
                <div class="mt-4 border-t border-slate-100 pt-4">
                    <div class="flex items-center justify-between text-sm">
                        <p class="text-slate-500">Kelengkapan checklist project: {{ $coverage['covered'] }} dari {{ $coverage['total'] }}</p>
                        <p class="font-semibold text-slate-900">{{ $coverage['percentage'] }}%</p>
                    </div>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $coverage['percentage'] }}%"></div>
                    </div>
                </div>
            @endif
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-500">#</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-500">Item</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-500">Requirement</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($selectedPackage->items as $item)
                        <tr wire:key="item-{{ $item->id }}">
                            <td class="px-4 py-3 text-slate-500">{{ $item->sort_order }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ $item->displayName() }}
                                <span class="ml-1 text-xs font-normal text-slate-400">({{ $item->document ? 'Dokumen' : 'Bukti' }})</span>
                            </td>
                            <td class="px-4 py-3 text-slate-500">
                                {{ $item->document?->documentRequirement?->name ?? $item->evidence?->documentRequirement?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @can('update', $project)
                                    @if ($selectedPackage->status->value === 'draft')
                                        <button type="button" wire:click="removeItem('{{ $item->id }}')" class="text-red-600 hover:underline">Keluarkan</button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-400">Manifest masih kosong.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @can('update', $project)
            @if ($selectedPackage->status->value === 'draft')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="mb-3 text-sm font-semibold text-slate-900">Tambah Dokumen</h3>
                        <div class="space-y-2">
                            @forelse ($availableDocuments as $document)
                                <div class="flex items-center justify-between text-sm" wire:key="avail-doc-{{ $document->id }}">
                                    <span>{{ $document->name }} (v{{ $document->version }})</span>
                                    <button type="button" wire:click="addDocument('{{ $document->id }}')" class="text-blue-600 hover:underline">+ Tambah</button>
                                </div>
                            @empty
                                <p class="text-sm text-slate-400">Tidak ada dokumen lain yang bisa ditambahkan.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="mb-3 text-sm font-semibold text-slate-900">Tambah Bukti Pendukung</h3>
                        <div class="space-y-2">
                            @forelse ($availableEvidences as $evidence)
                                <div class="flex items-center justify-between text-sm" wire:key="avail-evi-{{ $evidence->id }}">
                                    <span>{{ $evidence->name }}</span>
                                    <button type="button" wire:click="addEvidence('{{ $evidence->id }}')" class="text-blue-600 hover:underline">+ Tambah</button>
                                </div>
                            @empty
                                <p class="text-sm text-slate-400">Tidak ada bukti lain yang bisa ditambahkan.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif
        @endcan
    @endif
</div>
