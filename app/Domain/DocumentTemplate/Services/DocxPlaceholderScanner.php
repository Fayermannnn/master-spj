<?php

declare(strict_types=1);

namespace App\Domain\DocumentTemplate\Services;

use RuntimeException;
use ZipArchive;

/**
 * @domain DocumentTemplate
 *
 * Memindai placeholder `{{variable}}` di dalam file .docx (§64 master
 * prompt). DOCX adalah arsip ZIP — kita baca `word/document.xml` lewat
 * ZipArchive bawaan PHP (tidak perlu dependency tambahan untuk sekadar
 * mendeteksi placeholder; pemilihan library untuk MENGISI template
 * ditunda ke Phase 7/Document Generator — lihat PROJECT_DECISIONS.md
 * D-016).
 *
 * Word sering memecah satu placeholder jadi beberapa `<w:t>` run
 * terpisah karena formatting internal — men-strip SELURUH tag XML dulu
 * (bukan hanya <w:t>) sebelum regex menggabungkan run yang terpecah itu
 * secara alami.
 */
class DocxPlaceholderScanner
{
    private const string PLACEHOLDER_PATTERN = '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/';

    /**
     * @return list<string>
     */
    public function scan(string $absolutePath): array
    {
        $zip = new ZipArchive;

        if ($zip->open($absolutePath) !== true) {
            throw new RuntimeException('Tidak dapat membuka file sebagai DOCX (bukan arsip ZIP yang valid).');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException('File bukan DOCX yang valid — word/document.xml tidak ditemukan.');
        }

        $plainText = strip_tags($xml);

        preg_match_all(self::PLACEHOLDER_PATTERN, $plainText, $matches);

        return array_values(array_unique($matches[1]));
    }
}
