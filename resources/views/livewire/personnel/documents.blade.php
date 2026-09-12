<div class="space-y-4">
    @can('update', $personnel)
        <form wire:submit="upload" class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Jenis Dokumen</label>
                <select wire:model="document_type" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    @foreach ($documentTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-500">File (PDF/JPG/PNG/DOC, maks 5MB)</label>
                <input type="file" wire:model="file" class="block w-full text-sm">
                @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <div wire:loading wire:target="file" class="mt-1 text-xs text-slate-400">Mengunggah…</div>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Unggah
                </button>
            </div>
        </form>
    @endcan

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Jenis</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Nama File</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500">Diunggah</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($documents as $document)
                    <tr wire:key="doc-{{ $document->id }}">
                        <td class="px-4 py-3 text-slate-500">{{ $document->document_type->label() }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $document->original_filename }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $document->created_at->translatedFormat('d M Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('personnel-documents.download', $document) }}" class="text-blue-600 hover:underline">
                                    Unduh
                                </a>
                                @can('update', $personnel)
                                    <button
                                        type="button"
                                        wire:click="deleteDocument('{{ $document->id }}')"
                                        wire:confirm="Hapus dokumen \"{{ $document->original_filename }}\"?"
                                        class="text-red-600 hover:underline"
                                    >
                                        Hapus
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-400">Belum ada dokumen.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
