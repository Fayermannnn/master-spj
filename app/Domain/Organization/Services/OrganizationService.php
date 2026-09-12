<?php

declare(strict_types=1);

namespace App\Domain\Organization\Services;

use App\Domain\AuditLog\Services\AuditLogService;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Domain\Shared\Services\FileStorageService;
use App\Models\Organization;
use Illuminate\Http\UploadedFile;

/**
 * @domain Organization
 */
class OrganizationService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly FileStorageService $fileStorage,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Organization
    {
        $organization = Organization::query()->create($data);

        $this->auditLog->record('Organization', 'created', $organization, after: $organization->getAttributes());

        return $organization;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Organization $organization, array $data): Organization
    {
        $before = $organization->getAttributes();

        $organization->update($data);

        $this->auditLog->record('Organization', 'updated', $organization, before: $before, after: $organization->getChanges());

        return $organization;
    }

    /**
     * Logo dipakai sebagai kop surat otomatis (`organization.logo`,
     * PROJECT_DECISIONS.md D-030) — mengganti file lama kalau sudah
     * ada, supaya tidak ada file yatim tertinggal di disk.
     */
    public function updateLogo(Organization $organization, UploadedFile $file): Organization
    {
        if ($organization->logo_path !== null) {
            $this->fileStorage->delete((string) $organization->logo_disk, $organization->logo_path);
        }

        $stored = $this->fileStorage->store($file, "organization-logos/{$organization->id}");

        $before = $organization->getAttributes();

        $organization->update([
            'logo_disk' => $stored['disk'],
            'logo_path' => $stored['path'],
            'logo_original_filename' => $stored['original_filename'],
            'logo_mime_type' => $stored['mime_type'],
            'logo_size' => $stored['size'],
        ]);

        $this->auditLog->record('Organization', 'logo_updated', $organization, before: $before, after: $organization->getChanges());

        return $organization;
    }

    public function removeLogo(Organization $organization): Organization
    {
        if ($organization->logo_path !== null) {
            $this->fileStorage->delete((string) $organization->logo_disk, $organization->logo_path);
        }

        $before = $organization->getAttributes();

        $organization->update([
            'logo_disk' => null,
            'logo_path' => null,
            'logo_original_filename' => null,
            'logo_mime_type' => null,
            'logo_size' => null,
        ]);

        $this->auditLog->record('Organization', 'logo_removed', $organization, before: $before);

        return $organization;
    }

    public function delete(Organization $organization): void
    {
        if ($organization->users()->exists()) {
            throw new DomainActionException(
                "Organisasi \"{$organization->name}\" tidak dapat dihapus karena masih memiliki user aktif. Pindahkan atau nonaktifkan user tersebut terlebih dahulu."
            );
        }

        $before = $organization->getAttributes();

        $organization->delete();

        $this->auditLog->record('Organization', 'deleted', $organization, before: $before);
    }
}
