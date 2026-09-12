<?php

declare(strict_types=1);

namespace App\Domain\DocumentGenerator\Contracts;

/**
 * @domain DocumentGenerator
 *
 * Extension point untuk konversi DOCX -> PDF (§22 master prompt:
 * "Generate DOCX -> Convert PDF -> Save both"). Implementasi default
 * `LibreOfficePdfConverter` — lihat PROJECT_DECISIONS.md D-018.
 */
interface PdfConverterInterface
{
    /**
     * Mengonversi file DOCX (path absolut) menjadi PDF, mengembalikan
     * path absolut file PDF hasil konversi. Melempar RuntimeException
     * dengan pesan yang aman ditampilkan ke user kalau konversi gagal
     * (mis. binary tidak ditemukan) — DocumentGeneratorService
     * membungkusnya jadi DomainActionException.
     */
    public function convert(string $absoluteDocxPath): string;
}
