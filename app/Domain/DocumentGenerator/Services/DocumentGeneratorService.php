<?php

declare(strict_types=1);

namespace App\Domain\DocumentGenerator\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\DocumentGenerator\Contracts\PdfConverterInterface;
use App\Domain\DocumentRequirement\Enums\ChecklistStatus;
use App\Domain\DocumentRequirement\Services\ChecklistService;
use App\Domain\DocumentTemplate\Enums\TemplateStatus;
use App\Domain\Settings\Services\NumberingService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Domain\Shared\Services\FileStorageService;
use App\Models\Deliverable;
use App\Models\Document;
use App\Models\DocumentRequirement;
use App\Models\DocumentTemplate;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Exception\Exception as PhpWordException;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;

/**
 * @domain DocumentGenerator
 *
 * Orkestrasi: isi DocumentTemplate dengan variable nyata (VariableResolver
 * + input manual dari form) memakai `phpoffice/phpword` TemplateProcessor
 * (delimiter kustom `{{ }}`, lihat PROJECT_DECISIONS.md D-018), simpan
 * DOCX, konversi PDF (PdfConverterInterface), lalu catat sebagai
 * `Document` dengan data snapshot (§61-62 master prompt) dan otomatis
 * menandai ProjectChecklistItem terkait sebagai Fulfilled.
 */
class DocumentGeneratorService
{
    public function __construct(
        private readonly FileStorageService $fileStorage,
        private readonly VariableResolver $resolver,
        private readonly PdfConverterInterface $pdfConverter,
        private readonly AuditLogService $auditLog,
        private readonly ChecklistService $checklistService,
        private readonly NumberingService $numberingService,
    ) {}

    /**
     * @param  array<string, string>  $scalarValues  key placeholder (mis. "deliverable.name") => nilai final (auto-resolve sudah digabung oleh Livewire component)
     */
    public function generate(
        Project $project,
        DocumentRequirement $requirement,
        DocumentTemplate $template,
        array $scalarValues,
        ?Payment $payment,
        ?User $generatedBy,
        ?Deliverable $deliverable = null,
    ): Document {
        if ($template->document_requirement_id !== $requirement->id) {
            throw new DomainActionException('Template yang dipilih tidak sesuai dengan requirement dokumen ini.');
        }

        if ($template->status !== TemplateStatus::Active) {
            throw new DomainActionException('Hanya versi template yang berstatus Aktif yang dapat dipakai untuk generate dokumen.');
        }

        if ($deliverable !== null && $deliverable->project_id !== $project->id) {
            throw new DomainActionException('Deliverable yang dipilih harus berasal dari project yang sama.');
        }

        $project->loadMissing(['organization', 'client', 'ppkContact', 'contract', 'personnelAssignments.personnel']);

        // `document.number` TIDAK PERNAH datang dari form (lihat
        // VariableResolver::reservedKeys()) — di-inject di sini,
        // SATU-SATUNYA tempat yang benar-benar menaikkan counter
        // organisasi (PROJECT_DECISIONS.md D-029). null kalau
        // organisasi belum mengonfigurasi NumberingSetting sama sekali
        // (fitur opt-in) ATAU template tidak memakai placeholder ini.
        $documentNumber = null;

        if (in_array('document.number', $template->detected_variables ?? [], true)) {
            $documentNumber = $this->numberingService->nextNumber($project->organization);
            $scalarValues['document.number'] = $documentNumber ?? '';
        }

        $tables = $this->resolveApplicableTables($template, $project);

        $absoluteTemplatePath = Storage::disk($template->disk)->path($template->path);
        $tempDocxPath = tempnam(sys_get_temp_dir(), 'spj_doc_').'.docx';

        Settings::setOutputEscapingEnabled(true);

        try {
            $processor = new TemplateProcessor($absoluteTemplatePath);
            $processor->setMacroChars('{{', '}}');

            foreach ($scalarValues as $key => $value) {
                $processor->setValue($key, $value);
            }

            foreach ($tables as $representativeKey => $rows) {
                if ($rows === []) {
                    $processor->deleteRow($representativeKey);

                    continue;
                }

                $processor->cloneRowAndSetValues($representativeKey, $rows);
            }

            $processor->saveAs($tempDocxPath);
        } catch (PhpWordException $exception) {
            @unlink($tempDocxPath);

            throw new DomainActionException("Gagal mengisi template: {$exception->getMessage()}");
        }

        try {
            $tempPdfPath = $this->pdfConverter->convert($tempDocxPath);
        } catch (RuntimeException $exception) {
            @unlink($tempDocxPath);

            throw new DomainActionException($exception->getMessage());
        }

        $requirementSlug = Str::slug($requirement->code);
        $version = ((int) Document::query()
            ->where('project_id', $project->id)
            ->where('document_requirement_id', $requirement->id)
            ->max('version')) + 1;

        try {
            $docxFilename = "{$requirementSlug}-v{$version}.docx";
            $pdfFilename = "{$requirementSlug}-v{$version}.pdf";
            $directory = "generated-documents/{$project->id}";

            $storedDocx = $this->fileStorage->storeFromPath(
                $tempDocxPath,
                $directory,
                $docxFilename,
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            );

            $storedPdf = $this->fileStorage->storeFromPath(
                $tempPdfPath,
                $directory,
                $pdfFilename,
                'application/pdf',
            );
        } finally {
            @unlink($tempDocxPath);
            @unlink($tempPdfPath);
        }

        $dataSnapshot = $scalarValues;
        foreach ($tables as $representativeKey => $rows) {
            $dataSnapshot["table:{$representativeKey}"] = $rows;
        }

        $document = Document::query()->create([
            'project_id' => $project->id,
            'document_requirement_id' => $requirement->id,
            'document_template_id' => $template->id,
            'payment_id' => $payment?->id,
            'deliverable_id' => $deliverable?->id,
            'number' => $documentNumber,
            'version' => $version,
            'name' => $requirement->name,
            'data_snapshot' => $dataSnapshot,
            'disk' => $storedDocx['disk'],
            'path' => $storedDocx['path'],
            'original_filename' => $storedDocx['original_filename'],
            'mime_type' => $storedDocx['mime_type'],
            'size' => $storedDocx['size'],
            'pdf_disk' => $storedPdf['disk'],
            'pdf_path' => $storedPdf['path'],
            'pdf_original_filename' => $storedPdf['original_filename'],
            'pdf_size' => $storedPdf['size'],
            'generated_by' => $generatedBy?->id,
            'generated_at' => now(),
        ]);

        $this->auditLog->record('DocumentGenerator', 'generated', $document, after: [
            'project_id' => $project->id,
            'document_requirement_id' => $requirement->id,
            'document_template_id' => $template->id,
            'version' => $version,
        ]);

        $this->markChecklistFulfilled($project, $requirement);

        return $document;
    }

    public function delete(Document $document): void
    {
        $this->fileStorage->delete($document->disk, $document->path);

        if ($document->pdf_disk !== null && $document->pdf_path !== null) {
            $this->fileStorage->delete($document->pdf_disk, $document->pdf_path);
        }

        $before = $document->getAttributes();

        $document->delete();

        $this->auditLog->record('DocumentGenerator', 'deleted', $document, before: $before);
    }

    /**
     * Menentukan grup tabel yang benar-benar dipakai template ini
     * (placeholder anggotanya terdeteksi di file), dikembalikan sebagai
     * peta [representative_key => rows] — representative_key adalah
     * anggota grup yang benar-benar ada di `detected_variables`, dipakai
     * sebagai penanda baris untuk `cloneRowAndSetValues()`/`deleteRow()`.
     *
     * @return array<string, list<array<string, string>>>
     */
    private function resolveApplicableTables(DocumentTemplate $template, Project $project): array
    {
        $detected = $template->detected_variables ?? [];
        $tables = [];

        foreach ($this->resolver->tableGroups() as $group => $memberKeys) {
            $representativeKey = null;

            foreach ($memberKeys as $memberKey) {
                if (in_array($memberKey, $detected, true)) {
                    $representativeKey = $memberKey;
                    break;
                }
            }

            if ($representativeKey === null) {
                continue;
            }

            $tables[$representativeKey] = $this->resolver->resolveTableRows($group, $project);
        }

        return $tables;
    }

    private function markChecklistFulfilled(Project $project, DocumentRequirement $requirement): void
    {
        $item = $project->checklistItems()
            ->where('document_requirement_id', $requirement->id)
            ->first();

        if ($item !== null && $item->status !== ChecklistStatus::Fulfilled) {
            $this->checklistService->updateStatus($item, ChecklistStatus::Fulfilled);
        }
    }
}
