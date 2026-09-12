<?php

declare(strict_types=1);

use App\Domain\DocumentTemplate\Services\DocxPlaceholderScanner;

/**
 * Membuat file .docx minimal (arsip ZIP berisi word/document.xml) untuk
 * pengujian — tanpa perlu Word/LibreOffice sungguhan.
 */
function makeMinimalDocx(string $bodyXml): string
{
    $path = tempnam(sys_get_temp_dir(), 'docx_test_').'.docx';

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString(
        '[Content_Types].xml',
        '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"></Types>'
    );
    $zip->addFromString(
        'word/document.xml',
        '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$bodyXml.'</w:body></w:document>'
    );
    $zip->close();

    return $path;
}

afterEach(function (): void {
    foreach (glob(sys_get_temp_dir().'/docx_test_*.docx') ?: [] as $file) {
        @unlink($file);
    }
});

it('detects a placeholder written in a single run', function (): void {
    $path = makeMinimalDocx('<w:p><w:r><w:t>Nama project: {{project.name}}</w:t></w:r></w:p>');

    $variables = (new DocxPlaceholderScanner)->scan($path);

    expect($variables)->toBe(['project.name']);
});

it('detects a placeholder split across multiple runs, as Word often does', function (): void {
    $path = makeMinimalDocx(
        '<w:p><w:r><w:t>Nilai: {{</w:t></w:r><w:r><w:t>payment</w:t></w:r><w:r><w:t>.amount}}</w:t></w:r></w:p>'
    );

    $variables = (new DocxPlaceholderScanner)->scan($path);

    expect($variables)->toBe(['payment.amount']);
});

it('deduplicates repeated placeholders', function (): void {
    $path = makeMinimalDocx(
        '<w:p><w:r><w:t>{{project.name}} ... {{project.name}}</w:t></w:r></w:p>'
    );

    $variables = (new DocxPlaceholderScanner)->scan($path);

    expect($variables)->toBe(['project.name']);
});

it('returns an empty list when no placeholder exists', function (): void {
    $path = makeMinimalDocx('<w:p><w:r><w:t>Tidak ada placeholder di sini.</w:t></w:r></w:p>');

    $variables = (new DocxPlaceholderScanner)->scan($path);

    expect($variables)->toBe([]);
});

it('throws when the file is not a valid zip archive', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'not_a_docx_').'.docx';
    file_put_contents($path, 'this is not a zip file');

    expect(fn () => (new DocxPlaceholderScanner)->scan($path))
        ->toThrow(RuntimeException::class);

    @unlink($path);
});
