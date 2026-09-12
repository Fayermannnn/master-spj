<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\PermissionName;
use App\Models\Client;
use App\Models\User;

/**
 * @domain Client
 */
class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ClientsViewAny->value);
    }

    public function view(User $user, Client $client): bool
    {
        return $user->can(PermissionName::ClientsView->value)
            && $user->organization_id === $client->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::ClientsCreate->value);
    }

    public function update(User $user, Client $client): bool
    {
        return $user->can(PermissionName::ClientsUpdate->value)
            && $user->organization_id === $client->organization_id;
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->can(PermissionName::ClientsDelete->value)
            && $user->organization_id === $client->organization_id;
    }
}
