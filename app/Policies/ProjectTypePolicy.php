<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\PermissionName;
use App\Models\ProjectType;
use App\Models\User;

/**
 * @domain ProjectManagement
 *
 * ProjectType adalah master data global: semua user boleh melihat (untuk
 * memilih saat membuat project), hanya pemegang project_types.manage
 * (super_admin) yang boleh mengubahnya.
 */
class ProjectTypePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProjectType $projectType): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::ProjectTypesManage->value);
    }

    public function update(User $user, ProjectType $projectType): bool
    {
        return $user->can(PermissionName::ProjectTypesManage->value);
    }

    public function delete(User $user, ProjectType $projectType): bool
    {
        return $user->can(PermissionName::ProjectTypesManage->value);
    }
}
