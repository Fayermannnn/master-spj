<div class="space-y-5">
    <div class="mb-2">
        <a href="{{ route('document-requirements.index') }}" wire:navigate class="text-sm text-slate-500 hover:text-slate-700">
            &larr; Kembali ke daftar kebutuhan dokumen
        </a>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-lg font-semibold text-slate-900">{{ $documentRequirement->name }}</h1>
        <p class="mt-1 text-sm text-slate-500">
            Kelola versi template dokumen untuk kebutuhan ini. Kode: <span class="font-mono">{{ $documentRequirement->code }}</span>
        </p>
    </div>

    @can('create', \App\Models\DocumentTemplate::class)
        <form wire:submit="upload" class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-4">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-500">Nama Template</label>
                <input wire:model="name" type="text" placeholder="mis. Berita Acara Pembayaran" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-500">File (.docx, maks 10MB)</label>
                <input type="file" wire:model="file" accept=".docx" class="block w-full text-sm">
                @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <div wire:loading wire:target="file" class="mt-1 text-xs text-slate-400">Mengunggah…</div>
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-medium text-slate-500">Deskripsi</label>
                <input wire:model="description" type="text" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Unggah Versi Baru
                </button>
            </div>
        </form>
    @endcan

    @error('delete')
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $message }}
        </div>
    @enderror

    <div class="space-y-4">
        @forelse ($templates as $template)
            <div wire:key="template-{{ $template->id }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            {{ $template->name }} <span class="font-mono text-xs text-slate-400">v{{ $template->version }}</span>
                        </p>
                        <p class="text-xs text-slate-500">{{ $template->original_filename }} &middot; {{ number_format($template->size / 1024, 0) }} KB</p>
                        @if ($template->description)
                            <p class="mt-1 text-xs text-slate-400">{{ $template->description }}</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <x-template-status-badge :status="$template->status" />
                        <a href="{{ route('document-templates.download', $template) }}" class="text-sm text-blue-600 hover:underline">
                            Unduh
                        </a>
                        @can('update', \App\Models\DocumentTemplate::class)
                            @if ($template->status->value !== 'active')
                                <button type="button" wire:click="activate('{{ $template->id }}')" class="text-sm text-emerald-600 hover:underline">
                                    Aktifkan
                                </button>
                            @endif
                            @if ($template->status->value === 'draft')
                                <button type="button" wire:click="archive('{{ $template->id }}')" class="text-sm text-slate-500 hover:underline">
                                    Arsipkan
                                </button>
                            @endif
                        @endcan
                        @can('delete', \App\Models\DocumentTemplate::class)
                            <button
                                type="button"
                                wire:click="delete('{{ $template->id }}')"
                                wire:confirm="Hapus template \"{{ $template->name }}\" v{{ $template->version }}?"
                                class="text-sm text-red-600 hover:underline"
                            >
                                Hapus
                            </button>
                        @endcan
                    </div>
                </div>

                <div class="mt-3 border-t border-slate-100 pt-3">
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-400">
                        Variabel Terdeteksi ({{ count($template->detected_variables ?? []) }})
                    </p>
                    @if (empty($template->detected_variables))
                        @php $placeholderExample = '{{'.'...'.'}}'; @endphp
                        <p class="text-sm text-slate-400">Tidak ada placeholder {{ $placeholderExample }} terdeteksi di dalam file.</p>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($template->detected_variables as $variableKey)
                                @php $wrappedVariable = '{{'.$variableKey.'}}'; @endphp
                                @if (in_array($variableKey, $knownVariableKeys, true))
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 font-mono text-xs text-emerald-700">
                                        {{ $wrappedVariable }}
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 font-mono text-xs text-amber-700"
                                        title="Variabel ini tidak dikenal sistem — periksa penulisan atau tambahkan di halaman Variabel Template."
                                    >
                                        {{ $wrappedVariable }} ⚠
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white p-8 text-center text-sm text-slate-400 shadow-sm">
                Belum ada template diunggah untuk kebutuhan dokumen ini.
            </div>
        @endforelse
    </div>
</div>
