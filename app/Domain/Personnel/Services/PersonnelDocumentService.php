<?php

declare(strict_types=1);

namespace App\Domain\Personnel\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\Shared\Services\FileStorageService;
use App\Models\Personnel;
use App\Models\PersonnelDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * @domain Personnel
 */
class PersonnelDocumentService
{
    public function __construct(
        private readonly FileStorageService $fileStorage,
        private readonly AuditLogService $auditLog,
    ) {}

    public function upload(Personnel $personnel, UploadedFile $file, string $documentType, ?User $uploader, ?string $notes = null): PersonnelDocument
    {
        $stored = $this->fileStorage->store(
            $file,
            "personnel/{$personnel->organization_id}/{$personnel->id}/documents",
        );

        $document = $personnel->documents()->create([
            'document_type' => $documentType,
            'disk' => $stored['disk'],
            'path' => $stored['path'],
            'original_filename' => $stored['original_filename'],
            'mime_type' => $stored['mime_type'],
            'size' => $stored['size'],
            'uploaded_by' => $uploader?->id,
            'notes' => $notes,
        ]);

        $this->auditLog->record('Personnel', 'document_uploaded', $document, after: [
            'personnel_id' => $personnel->id,
            'document_type' => $documentType,
            'original_filename' => $stored['original_filename'],
        ]);

        return $document;
    }

    public function delete(PersonnelDocument $document): void
    {
        $this->fileStorage->delete($document->disk, $document->path);

        $before = $document->getAttributes();

        $document->delete();

        $this->auditLog->record('Personnel', 'document_deleted', $document, before: $before);
    }
}
