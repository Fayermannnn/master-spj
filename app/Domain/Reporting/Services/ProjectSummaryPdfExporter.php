<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Services;

use App\Domain\DocumentGenerator\Contracts\PdfConverterInterface;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

/**
 * @domain Reporting
 *
 * Reuse penuh `PdfConverterInterface` (LibreOffice headless) dari
 * DocumentGenerator (Phase 7) — bangun tabel laporan sebagai DOCX lewat
 * `phpoffice/phpword` (sudah terpasang), lalu konversi PDF lewat pipeline
 * yang sama, BUKAN menambah library PDF baru (mis. dompdf/snappy) hanya
 * untuk satu laporan tabel sederhana (RULE 67).
 */
class ProjectSummaryPdfExporter
{
    private const array HEADERS = [
        'Kode', 'Project', 'Klien', 'Status', 'Nilai Kontrak', 'Total Dibayar', 'Kelengkapan',
    ];

    public function __construct(private readonly PdfConverterInterface $pdfConverter) {}

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return string path absolut file PDF sementara
     */
    public function export(Collection $rows): string
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection(['orientation' => 'landscape']);
        $section->addText('Laporan Ringkasan Project', ['bold' => true, 'size' => 14]);
        $section->addText('Dicetak: '.now()->translatedFormat('d F Y H:i'), ['size' => 9, 'color' => '666666']);
        $section->addTextBreak();

        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);

        $table->addRow();
        foreach (self::HEADERS as $header) {
            $table->addCell(2200)->addText($header, ['bold' => true]);
        }

        foreach ($rows as $row) {
            $table->addRow();
            $table->addCell(2200)->addText((string) $row['code']);
            $table->addCell(2200)->addText((string) $row['name']);
            $table->addCell(2200)->addText((string) $row['client']);
            $table->addCell(2200)->addText((string) $row['status']);
            $table->addCell(2200)->addText($this->formatCurrency($row['contract_value']));
            $table->addCell(2200)->addText($this->formatCurrency($row['total_paid']));
            $table->addCell(2200)->addText("{$row['checklist_fulfilled']}/{$row['checklist_total']} ({$row['checklist_percentage']}%)");
        }

        $docxPath = tempnam(sys_get_temp_dir(), 'report_docx_').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($docxPath);

        try {
            return $this->pdfConverter->convert($docxPath);
        } finally {
            @unlink($docxPath);
        }
    }

    private function formatCurrency(mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
