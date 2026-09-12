<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\PermissionName;
use App\Models\User;

/**
 * @domain Identity
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::UsersViewAny->value);
    }

    public function view(User $user, User $target): bool
    {
        return $user->can(PermissionName::UsersView->value)
            && $user->organization_id === $target->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::UsersCreate->value);
    }

    public function update(User $user, User $target): bool
    {
        return $user->can(PermissionName::UsersUpdate->value)
            && $user->organization_id === $target->organization_id;
    }

    public function delete(User $user, User $target): bool
    {
        return $user->can(PermissionName::UsersDelete->value)
            && $user->organization_id === $target->organization_id
            && $user->isNot($target);
    }
}
