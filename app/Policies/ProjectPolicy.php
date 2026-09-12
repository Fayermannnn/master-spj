<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\PermissionName;
use App\Models\Project;
use App\Models\User;

/**
 * @domain ProjectManagement
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ProjectsViewAny->value);
    }

    public function view(User $user, Project $project): bool
    {
        return $user->can(PermissionName::ProjectsView->value)
            && $user->organization_id === $project->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::ProjectsCreate->value);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->can(PermissionName::ProjectsUpdate->value)
            && $user->organization_id === $project->organization_id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->can(PermissionName::ProjectsDelete->value)
            && $user->organization_id === $project->organization_id;
    }

    public function transitionStatus(User $user, Project $project): bool
    {
        return $user->can(PermissionName::ProjectsTransitionStatus->value)
            && $user->organization_id === $project->organization_id;
    }
}
