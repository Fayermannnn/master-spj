<?php

declare(strict_types=1);

namespace App\Domain\Spj\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Models\SpjItem;
use App\Models\SpjPackage;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * @domain Spj
 *
 * Menyusun ZIP berisi PDF setiap `Document` (fallback DOCX kalau tanpa
 * PDF) dan file setiap `Evidence` dalam manifest paket, plus
 * `manifest.txt` sebagai daftar isi. Memakai `ZipArchive` bawaan PHP,
 * tanpa dependency composer baru (pola sama dengan
 * `DocxPlaceholderScanner`, PROJECT_DECISIONS.md D-016). Dibangun
 * sinkron ke file temp — jumlah item per paket kecil (satuan/puluhan),
 * tidak perlu antrean (RULE 67, jangan over-engineer).
 */
class SpjExportService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * @return string path absolut file ZIP sementara — pemanggil
     *                bertanggung jawab menghapusnya setelah dikirim.
     */
    public function export(SpjPackage $package): string
    {
        $package->loadMissing(['project', 'payment', 'items.document.documentRequirement', 'items.evidence.documentRequirement']);

        $zipPath = tempnam(sys_get_temp_dir(), 'spj_export_').'.zip';
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat file ZIP.');
        }

        $manifest = $this->manifestHeader($package);
        $items = $package->items()->with(['document.documentRequirement', 'evidence.documentRequirement'])->orderBy('sort_order')->get();

        $index = 1;
        foreach ($items as $item) {
            $source = $this->resolveSourceFile($item);

            if ($source === null) {
                continue;
            }

            [$disk, $path] = $source;
            $absolute = Storage::disk($disk)->path($path);
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $safeName = (string) preg_replace('/[\\\\\/:*?"<>|]/', '-', $item->displayName());
            $entryName = sprintf('%02d - %s.%s', $index, $safeName, $extension);

            $zip->addFile($absolute, $entryName);

            $requirementName = $this->requirementNameFor($item);
            $manifest[] = sprintf('%02d. %s%s', $index, $item->displayName(), $requirementName !== null ? " — memenuhi: {$requirementName}" : '');

            $index++;
        }

        $zip->addFromString('manifest.txt', implode("\n", $manifest));
        $zip->close();

        $this->auditLog->record('Spj', 'package_exported', $package, after: ['item_count' => $items->count()]);

        return $zipPath;
    }

    /**
     * @return array{0: string, 1: string}|null [disk, path]
     */
    private function resolveSourceFile(SpjItem $item): ?array
    {
        if ($item->document !== null) {
            $document = $item->document;

            return $document->hasPdf()
                ? [(string) $document->pdf_disk, (string) $document->pdf_path]
                : [$document->disk, $document->path];
        }

        if ($item->evidence !== null) {
            return [$item->evidence->disk, $item->evidence->path];
        }

        return null;
    }

    private function requirementNameFor(SpjItem $item): ?string
    {
        $document = $item->document;

        if ($document !== null) {
            return $document->documentRequirement?->name;
        }

        $evidence = $item->evidence;

        return $evidence !== null ? $evidence->documentRequirement?->name : null;
    }

    /**
     * @return list<string>
     */
    private function manifestHeader(SpjPackage $package): array
    {
        $project = $package->project;
        $createdAt = $package->created_at;

        return [
            "PAKET SPJ: {$package->name}",
            'Project: '.($project !== null ? $project->name : '—'),
            $package->payment !== null
                ? "Cakupan: Termin {$package->payment->termin_number} — {$package->payment->name}"
                : 'Cakupan: Level Project (SPJ Akhir)',
            "Status: {$package->status->label()}",
            'Dibuat: '.($createdAt !== null ? $createdAt->translatedFormat('d F Y H:i') : '—'),
            '',
            'DAFTAR ISI:',
        ];
    }
}
