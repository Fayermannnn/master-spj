<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

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

    public function delete(string $disk, string $path): void
    {
        Storage::disk($disk)->delete($path);
    }

    public function exists(string $disk, string $path): bool
    {
        return Storage::disk($disk)->exists($path);
    }
}
