<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\PermissionName;
use App\Models\DocumentRequirement;
use App\Models\User;

/**
 * @domain DocumentRequirement
 *
 * Master data global — semua user boleh melihat (checklist project
 * membutuhkannya), hanya document_requirements.manage (super_admin) yang
 * boleh mengubah.
 */
class DocumentRequirementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DocumentRequirement $documentRequirement): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::DocumentRequirementsManage->value);
    }

    public function update(User $user, DocumentRequirement $documentRequirement): bool
    {
        return $user->can(PermissionName::DocumentRequirementsManage->value);
    }

    public function delete(User $user, DocumentRequirement $documentRequirement): bool
    {
        return $user->can(PermissionName::DocumentRequirementsManage->value);
    }
}
