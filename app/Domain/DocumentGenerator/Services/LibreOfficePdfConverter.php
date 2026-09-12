<?php

declare(strict_types=1);

namespace App\Domain\DocumentGenerator\Services;

use App\Domain\DocumentGenerator\Contracts\PdfConverterInterface;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * @domain DocumentGenerator
 *
 * Konversi DOCX -> PDF lewat LibreOffice headless (`soffice --headless
 * --convert-to pdf`), dijalankan via `Illuminate\Support\Facades\Process`
 * (bawaan framework, bukan dependency baru — lihat PROJECT_DECISIONS.md
 * D-018). Binary dikonfigurasi lewat `config('services.libreoffice.binary')`
 * (env `LIBREOFFICE_BINARY`, default `soffice`).
 */
class LibreOfficePdfConverter implements PdfConverterInterface
{
    public function convert(string $absoluteDocxPath): string
    {
        $binary = (string) config('services.libreoffice.binary', 'soffice');
        $outputDir = dirname($absoluteDocxPath);

        $result = Process::timeout(60)->run([
            $binary, '--headless', '--norestore', '--convert-to', 'pdf', '--outdir', $outputDir, $absoluteDocxPath,
        ]);

        if ($result->failed()) {
            throw new RuntimeException(
                "Konversi PDF gagal — pastikan LibreOffice terpasang dan dapat dijalankan (\"{$binary}\"). Detail: ".trim($result->errorOutput() ?: $result->output())
            );
        }

        $expectedPdfPath = preg_replace('/\.docx$/i', '.pdf', $absoluteDocxPath);

        if ($expectedPdfPath === null || ! is_file($expectedPdfPath)) {
            throw new RuntimeException('Konversi PDF gagal — file PDF hasil konversi tidak ditemukan.');
        }

        return $expectedPdfPath;
    }
}
