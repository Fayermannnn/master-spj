<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @domain Shared
 *
 * Wrapper tipis di atas Laravel Filesystem, dipakai lintas domain
 * (PersonnelDocument sekarang; Evidence/DocumentGenerator di fase
 * mendatang — lihat PROJECT_DECISIONS.md D-011). Sengaja TIDAK
 * memvalidasi MIME/ukuran di sini — itu tanggung jawab validation rules
 * di form/Livewire component (batas sistem yang sesungguhnya), service
 * ini hanya menyimpan file yang sudah lolos validasi.
 *
 * Disk default `local` (storage/app/private, TIDAK bisa diakses langsung
 * lewat URL publik) — file harus di-download lewat route yang dijaga
 * Policy, bukan link statis (RULE 41/42 master prompt).
 */
class FileStorageService
{
    private const string DEFAULT_DISK = 'local';

    /**
     * @return array{disk: string, path: string, original_filename: string, mime_type: string, size: int}
     */
    public function store(UploadedFile $file, string $directory, ?string $disk = null): array
    {
        $disk ??= self::DEFAULT_DISK;
        $path = $file->store($directory, $disk);

        if ($path === false) {
            throw new RuntimeException("Gagal menyimpan file ke direktori \"{$directory}\".");
        }

        return [
            'disk' => $disk,
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
        ];
    }

    /**
     * Menyimpan file yang sudah ada di disk lokal (bukan hasil upload
     * HTTP) — dipakai DocumentGenerator untuk menyimpan file DOCX/PDF
     * hasil generate, sebagai method tambahan (additive) di samping
     * `store()` yang khusus `UploadedFile`, tanpa mengubah kontrak lama.
     *
     * @return array{disk: string, path: string, original_filename: string, mime_type: string, size: int}
     */
    public function storeFromPath(string $absolutePath, string $directory, string $filename, string $mimeType, ?string $disk = null): array
    {
        $disk ??= self::DEFAULT_DISK;
        $path = Storage::disk($disk)->putFileAs($directory, new File($absolutePath), $filename);

        if ($path === false) {
            throw new RuntimeException("Gagal menyimpan file ke direktori \"{$directory}\".");
        }

        return [
            'disk' => $disk,
            'path' => $path,
            'original_filename' => $filename,
            'mime_type' => $mimeType,
            'size' => filesize($absolutePath) ?: 0,
        ];
    }

    public function delete(string $disk, string $path): void
    {
        Storage::disk($disk)->delete($path);
    }

    public function exists(string $disk, string $path): bool
    {
        return Storage::disk($disk)->exists($path);
    }

    /**
     * Titik tunggal untuk mengunduh file lewat route yang dijaga
     * Policy — dipakai SETELAH `Gate::authorize()` di controller.
     * Sebelum ini setiap download controller memanggil
     * `Storage::disk()->download()` langsung, yang melempar exception
     * Flysystem mentah (500) kalau baris DB masih ada tapi file fisik
     * sudah hilang dari disk (dihapus manual, storage rusak, dst) —
     * bukan penghapusan lewat alur resmi (yang menghapus file+row
     * bersamaan). Dicek eksplisit di sini supaya kegagalan itu jadi 404
     * terkontrol untuk SEMUA download controller sekaligus.
     */
    public function download(string $disk, string $path, string $downloadName): StreamedResponse
    {
        abort_unless($this->exists($disk, $path), 404, 'Berkas tidak ditemukan di penyimpanan.');

        return Storage::disk($disk)->download($path, $downloadName);
    }

    /**
     * Sama seperti `download()` (exists-check + 404 terkontrol), tapi
     * `Content-Disposition: inline` — dipakai untuk pratinjau gambar
     * langsung di halaman (mis. logo organisasi), bukan diunduh sebagai
     * file terpisah.
     */
    public function inline(string $disk, string $path): StreamedResponse
    {
        abort_unless($this->exists($disk, $path), 404, 'Berkas tidak ditemukan di penyimpanan.');

        return Storage::disk($disk)->response($path);
    }
}
