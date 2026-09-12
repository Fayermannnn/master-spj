<?php

declare(strict_types=1);

namespace App\Livewire\DocumentTemplates;

use App\Domain\DocumentTemplate\Enums\TemplateStatus;
use App\Domain\DocumentTemplate\Services\DocumentTemplateService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Models\DocumentRequirement;
use App\Models\DocumentTemplate;
use App\Models\TemplateVariable;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

#[Layout('layouts.app')]
class Manager extends Component
{
    use WithFileUploads;

    public DocumentRequirement $documentRequirement;

    #[Validate('required|file|mimes:docx|max:10240')]
    public mixed $file = null;

    public string $name = '';

    public string $description = '';

    public function mount(DocumentRequirement $documentRequirement): void
    {
        $this->documentRequirement = $documentRequirement;

        $this->authorize('viewAny', DocumentTemplate::class);
    }

    public function upload(DocumentTemplateService $service): void
    {
        $this->authorize('create', DocumentTemplate::class);

        $this->validate([
            'file' => 'required|file|mimes:docx|max:10240',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        /** @var User $viewer */
        $viewer = Auth::user();

        try {
            $service->upload($this->documentRequirement, $this->file, $this->name, $this->description ?: null, $viewer);
            $this->reset(['file', 'name', 'description']);
            session()->flash('status', 'Template berhasil diunggah.');
        } catch (RuntimeException $exception) {
            $this->addError('file', $exception->getMessage());
        }
    }

    public function activate(string $templateId, DocumentTemplateService $service): void
    {
        $this->authorize('update', DocumentTemplate::class);

        $template = $this->documentRequirement->templates()->findOrFail($templateId);
        $service->activate($template);

        session()->flash('status', "Template v{$template->version} sekarang aktif.");
    }

    public function archive(string $templateId, DocumentTemplateService $service): void
    {
        $this->authorize('update', DocumentTemplate::class);

        $template = $this->documentRequirement->templates()->findOrFail($templateId);
        $service->archive($template);

        session()->flash('status', "Template v{$template->version} diarsipkan.");
    }

    public function delete(string $templateId, DocumentTemplateService $service): void
    {
        $this->authorize('delete', DocumentTemplate::class);

        $template = $this->documentRequirement->templates()->findOrFail($templateId);

        try {
            $service->delete($template);
            session()->flash('status', 'Template berhasil dihapus.');
        } catch (DomainActionException $exception) {
            $this->addError('delete', $exception->getMessage());
        }
    }

    /**
     * @return array<int, string>
     */
    public function knownVariableKeys(): array
    {
        return TemplateVariable::query()
            ->pluck('key')
            ->map(static fn (mixed $key): string => (string) $key)
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('livewire.document-templates.manager', [
            'templates' => $this->documentRequirement->templates()->orderByDesc('version')->get(),
            'knownVariableKeys' => $this->knownVariableKeys(),
            'statuses' => TemplateStatus::cases(),
        ])->title("Template — {$this->documentRequirement->name}");
    }
}
