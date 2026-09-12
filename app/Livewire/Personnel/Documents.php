<?php

declare(strict_types=1);

namespace App\Livewire\Personnel;

use App\Domain\Personnel\Enums\PersonnelDocumentType;
use App\Domain\Personnel\Services\PersonnelDocumentService;
use App\Models\Personnel;
use App\Models\PersonnelDocument;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Komponen nested — dirender di dalam tab "Dokumen" pada Personnel\Show.
 */
class Documents extends Component
{
    use WithFileUploads;

    public Personnel $personnel;

    #[Validate('required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120')]
    public mixed $file = null;

    #[Validate('required|string')]
    public string $document_type = '';

    public string $notes = '';

    public function mount(Personnel $personnel): void
    {
        $this->personnel = $personnel;
        $this->document_type = PersonnelDocumentType::Ktp->value;

        $this->authorize('update', $this->personnel);
    }

    public function upload(PersonnelDocumentService $service): void
    {
        $this->authorize('update', $this->personnel);

        $this->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'document_type' => 'required|string|in:'.implode(',', array_map(fn ($case) => $case->value, PersonnelDocumentType::cases())),
            'notes' => 'nullable|string|max:500',
        ]);

        /** @var User $viewer */
        $viewer = Auth::user();

        $service->upload($this->personnel, $this->file, $this->document_type, $viewer, $this->notes ?: null);

        $this->reset(['file', 'notes']);
        session()->flash('status', 'Dokumen berhasil diunggah.');
    }

    public function deleteDocument(string $documentId, PersonnelDocumentService $service): void
    {
        $document = PersonnelDocument::query()->where('personnel_id', $this->personnel->id)->findOrFail($documentId);
        $this->authorize('update', $this->personnel);

        $service->delete($document);
        session()->flash('status', 'Dokumen berhasil dihapus.');
    }

    public function render(): View
    {
        return view('livewire.personnel.documents', [
            'documents' => $this->personnel->documents()->latest()->get(),
            'documentTypes' => PersonnelDocumentType::cases(),
        ]);
    }
}
