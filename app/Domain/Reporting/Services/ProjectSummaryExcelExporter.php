<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @domain Reporting
 *
 * Memakai `phpoffice/phpspreadsheet` langsung (bukan wrapper package
 * seperti maatwebsite/laravel-excel) — konsisten dengan gaya aplikasi
 * ini yang selalu memakai library inti secara langsung (`ZipArchive`,
 * `phpoffice/phpword` `TemplateProcessor`), bukan lapisan abstraksi
 * tambahan (RULE 67).
 */
class ProjectSummaryExcelExporter
{
    private const array HEADERS = [
        'Kode', 'Nama Project', 'Klien', 'Status', 'Nilai Kontrak',
        'Total Termin', 'Total Dibayar', 'Checklist Terpenuhi',
        'Checklist Total', 'Kelengkapan (%)',
    ];

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return string path absolut file XLSX sementara
     */
    public function export(Collection $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ringkasan Project');
        $sheet->fromArray(self::HEADERS, null, 'A1');

        $rowIndex = 2;

        foreach ($rows as $row) {
            $sheet->fromArray([
                $row['code'],
                $row['name'],
                $row['client'],
                $row['status'],
                $row['contract_value'] !== null ? (float) $row['contract_value'] : null,
                $row['total_payment'],
                $row['total_paid'],
                $row['checklist_fulfilled'],
                $row['checklist_total'],
                $row['checklist_percentage'],
            ], null, "A{$rowIndex}");
            $rowIndex++;
        }

        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'report_xlsx_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
