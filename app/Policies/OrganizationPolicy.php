<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\PermissionName;
use App\Domain\Identity\Enums\RoleName;
use App\Models\Organization;
use App\Models\User;

/**
 * @domain Identity
 */
class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::OrganizationsViewAny->value);
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->can(PermissionName::OrganizationsView->value)
            || $user->organization_id === $organization->id;
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::OrganizationsCreate->value);
    }

    public function update(User $user, Organization $organization): bool
    {
        if ($user->can(PermissionName::OrganizationsUpdate->value)) {
            return true;
        }

        return $user->hasRole(RoleName::AdminPerusahaan->value)
            && $user->organization_id === $organization->id;
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->can(PermissionName::OrganizationsDelete->value);
    }
}
