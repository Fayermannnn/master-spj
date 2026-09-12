<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\PermissionName;
use App\Models\DocumentTemplate;
use App\Models\User;

/**
 * @domain DocumentTemplate
 *
 * Template adalah sub-resource dari DocumentRequirement (master data
 * global) — sengaja memakai permission YANG SAMA
 * (document_requirements.manage) alih-alih permission baru, karena hanya
 * super_admin yang boleh mengelola requirement sekaligus template-nya.
 */
class DocumentTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DocumentTemplate $documentTemplate): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::DocumentRequirementsManage->value);
    }

    public function update(User $user, DocumentTemplate $documentTemplate): bool
    {
        return $user->can(PermissionName::DocumentRequirementsManage->value);
    }

    public function delete(User $user, DocumentTemplate $documentTemplate): bool
    {
        return $user->can(PermissionName::DocumentRequirementsManage->value);
    }
}
