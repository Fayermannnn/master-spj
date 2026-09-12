<div class="space-y-4">
    @error('generate')
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $message }}
        </div>
    @enderror

    @forelse ($items as $item)
        @php
            $template = $this->activeTemplateFor($item->document_requirement_id);
            $documents = $this->generatedDocumentsFor($item->document_requirement_id);
            $isOpen = $selectedRequirementId === $item->document_requirement_id;
        @endphp
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" wire:key="doc-req-{{ $item->id }}">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $item->documentRequirement->name }}</p>
                    <p class="text-xs text-slate-500">{{ $item->documentRequirement->category ?? '—' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <x-checklist-status-badge :status="$item->status" />
                    @can('update', $project)
                        @if ($template !== null)
                            <button
                                type="button"
                                wire:click="openGenerateForm('{{ $item->document_requirement_id }}')"
                                class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700"
                            >
                                Generate Dokumen
                            </button>
                        @else
                            <span class="text-xs text-slate-400">Belum ada template aktif</span>
                        @endif
                    @endif
                </div>
            </div>

            @if ($isOpen && $template !== null)
                <div class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Template v{{ $template->version }} — {{ $template->name }}
                    </p>

                    @if ($this->needsPaymentContext($template))
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">Termin/Pembayaran Terkait</label>
                            <select wire:model.live="selectedPaymentId" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                                <option value="">— Tidak terkait termin tertentu —</option>
                                @foreach ($payments as $payment)
                                    <option value="{{ $payment->id }}">Termin {{ $payment->termin_number }} — {{ $payment->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($this->needsDocumentNumber($template))
                        <p class="rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-500">
                            Placeholder "document.number" akan diisi otomatis dari nomor urut organisasi saat dokumen ini digenerate — tidak bisa diisi manual.
                        </p>
                    @endif

                    @if ($this->needsDeliverableContext($template))
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">Deliverable Terkait (opsional)</label>
                            <select wire:model.live="selectedDeliverableId" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                                <option value="">— Isi manual di bawah —</option>
                                @foreach ($deliverables as $deliverable)
                                    <option value="{{ $deliverable->id }}">{{ $deliverable->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-400">Memilih Deliverable mengisi otomatis kolom "deliverable.name" di bawah — tetap bisa diedit manual.</p>
                        </div>
                    @endif

                    @if ($this->needsSalaryContext($template))
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">Item Biaya Personel (untuk Slip Gaji)</label>
                            <select wire:model.live="selectedCostItemId" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                                <option value="">— Isi manual di bawah —</option>
                                @foreach ($this->salaryCostItemOptions() as $costItem)
                                    <option value="{{ $costItem->id }}">{{ $costItem->personnelAssignment?->personnel?->name }} — {{ $costItem->description }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-400">Memilih item biaya mengisi otomatis kolom "salary.*" di bawah — tetap bisa diedit manual.</p>
                        </div>
                    @endif

                    @if ($this->needsTravelContext($template))
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500">Perjalanan Dinas Terkait (untuk SPPD)</label>
                            <select wire:model.live="selectedTravelAssignmentId" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                                <option value="">— Isi manual di bawah —</option>
                                @foreach ($this->travelAssignmentOptions() as $travel)
                                    <option value="{{ $travel->id }}">{{ $travel->personnel?->name }} — {{ $travel->destination }} ({{ $travel->departure_date->translatedFormat('d M Y') }})</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-400">Memilih perjalanan dinas mengisi otomatis kolom "travel.*" di bawah — tetap bisa diedit manual. Tambah/kelola data perjalanan dinas di tab "Personel".</p>
                        </div>
                    @endif

                    @foreach ($this->tablelessDetectedKeys($template) as $index => $key)
                        <div>
                            @php $wrappedKey = '{{'.$key.'}}'; @endphp
                            <label class="mb-1 block text-xs font-medium text-slate-500">{{ $wrappedKey }}</label>
                            <input type="text" wire:model="variableInputs.{{ $index }}" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm" />
                        </div>
                    @endforeach

                    <div class="flex gap-2 pt-2">
                        <button type="button" wire:click="generate" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Generate
                        </button>
                        <button type="button" wire:click="closeGenerateForm" class="rounded-md border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                    </div>
                </div>
            @endif

            @if ($documents->isNotEmpty())
                <div class="mt-4 overflow-x-auto border-t border-slate-100 pt-4">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead>
                            <tr>
                                <th class="px-2 py-2 text-left font-medium text-slate-500">Versi</th>
                                <th class="px-2 py-2 text-left font-medium text-slate-500">Dibuat</th>
                                <th class="px-2 py-2 text-left font-medium text-slate-500">Oleh</th>
                                <th class="px-2 py-2 text-right font-medium text-slate-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($documents as $document)
                                <tr wire:key="document-{{ $document->id }}">
                                    <td class="px-2 py-2 text-slate-900">v{{ $document->version }}</td>
                                    <td class="px-2 py-2 text-slate-500">{{ $document->generated_at->translatedFormat('d M Y H:i') }}</td>
                                    <td class="px-2 py-2 text-slate-500">{{ $document->generatedBy?->name ?? '—' }}</td>
                                    <td class="px-2 py-2 text-right">
                                        <div class="flex justify-end gap-3">
                                            <a href="{{ route('generated-documents.download', ['document' => $document, 'type' => 'docx']) }}" class="text-blue-600 hover:underline">DOCX</a>
                                            @if ($document->hasPdf())
                                                <a href="{{ route('generated-documents.download', ['document' => $document, 'type' => 'pdf']) }}" class="text-blue-600 hover:underline">PDF</a>
                                            @endif
                                            @can('update', $project)
                                                <button type="button" wire:click="delete('{{ $document->id }}')" wire:confirm="Hapus dokumen versi {{ $document->version }}?" class="text-red-600 hover:underline">
                                                    Hapus
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-lg border border-slate-200 bg-white p-8 text-center text-sm text-slate-400 shadow-sm">
            Tidak ada dokumen yang berlaku untuk project ini.
        </div>
    @endforelse
</div>
