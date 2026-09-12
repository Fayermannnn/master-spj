<?php

declare(strict_types=1);

namespace App\Domain\DocumentTemplate\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\DocumentTemplate\Enums\TemplateStatus;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Domain\Shared\Services\FileStorageService;
use App\Models\DocumentRequirement;
use App\Models\DocumentTemplate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @domain DocumentTemplate
 */
class DocumentTemplateService
{
    public function __construct(
        private readonly FileStorageService $fileStorage,
        private readonly DocxPlaceholderScanner $scanner,
        private readonly AuditLogService $auditLog,
    ) {}

    public function upload(
        DocumentRequirement $requirement,
        UploadedFile $file,
        string $name,
        ?string $description,
        ?User $uploader,
    ): DocumentTemplate {
        $stored = $this->fileStorage->store($file, "document-templates/{$requirement->id}");

        $detectedVariables = $this->scanner->scan(
            Storage::disk($stored['disk'])->path($stored['path'])
        );

        $nextVersion = ((int) $requirement->templates()->max('version')) + 1;

        $template = $requirement->templates()->create([
            'name' => $name,
            'version' => $nextVersion,
            'status' => TemplateStatus::Draft->value,
            'disk' => $stored['disk'],
            'path' => $stored['path'],
            'original_filename' => $stored['original_filename'],
            'mime_type' => $stored['mime_type'],
            'size' => $stored['size'],
            'detected_variables' => $detectedVariables,
            'description' => $description,
            'uploaded_by' => $uploader?->id,
        ]);

        $this->auditLog->record('DocumentTemplate', 'uploaded', $template, after: [
            'document_requirement_id' => $requirement->id,
            'version' => $nextVersion,
            'detected_variables' => $detectedVariables,
        ]);

        return $template;
    }

    /**
     * Mengaktifkan satu versi template — otomatis meng-arsipkan versi
     * lain yang sebelumnya Active pada requirement yang sama (RULE §63:
     * hanya satu versi "berlaku" pada satu waktu).
     */
    public function activate(DocumentTemplate $template): DocumentTemplate
    {
        DocumentTemplate::query()
            ->where('document_requirement_id', $template->document_requirement_id)
            ->where('status', TemplateStatus::Active->value)
            ->where('id', '!=', $template->id)
            ->get()
            ->each(function (DocumentTemplate $previouslyActive): void {
                $previouslyActive->update(['status' => TemplateStatus::Archived->value]);
                $this->auditLog->record('DocumentTemplate', 'archived', $previouslyActive);
            });

        $template->update(['status' => TemplateStatus::Active->value]);
        $this->auditLog->record('DocumentTemplate', 'activated', $template);

        return $template;
    }

    public function archive(DocumentTemplate $template): DocumentTemplate
    {
        $template->update(['status' => TemplateStatus::Archived->value]);
        $this->auditLog->record('DocumentTemplate', 'archived', $template);

        return $template;
    }

    public function delete(DocumentTemplate $template): void
    {
        if ($template->status === TemplateStatus::Active) {
            throw new DomainActionException(
                "Versi template \"{$template->name}\" (v{$template->version}) sedang aktif dan tidak dapat dihapus. Arsipkan atau aktifkan versi lain terlebih dahulu."
            );
        }

        $this->fileStorage->delete($template->disk, $template->path);

        $before = $template->getAttributes();

        $template->delete();

        $this->auditLog->record('DocumentTemplate', 'deleted', $template, before: $before);
    }
}
