<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\PermissionName;
use App\Models\Personnel;
use App\Models\User;

/**
 * @domain Personnel
 */
class PersonnelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::PersonnelViewAny->value);
    }

    public function view(User $user, Personnel $personnel): bool
    {
        return $user->can(PermissionName::PersonnelView->value)
            && $user->organization_id === $personnel->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::PersonnelCreate->value);
    }

    public function update(User $user, Personnel $personnel): bool
    {
        return $user->can(PermissionName::PersonnelUpdate->value)
            && $user->organization_id === $personnel->organization_id;
    }

    public function delete(User $user, Personnel $personnel): bool
    {
        return $user->can(PermissionName::PersonnelDelete->value)
            && $user->organization_id === $personnel->organization_id;
    }
}
