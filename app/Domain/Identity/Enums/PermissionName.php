<?php

declare(strict_types=1);

namespace App\Domain\Identity\Enums;

/**
 * Permission list bertambah per fase. Jangan menambah permission untuk
 * modul yang belum dibangun — lihat PROJECT_BLUEPRINT.md §9 roadmap fase.
 */
enum PermissionName: string
{
    case OrganizationsViewAny = 'organizations.viewAny';
    case OrganizationsView = 'organizations.view';
    case OrganizationsCreate = 'organizations.create';
    case OrganizationsUpdate = 'organizations.update';
    case OrganizationsDelete = 'organizations.delete';

    case UsersViewAny = 'users.viewAny';
    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersDelete = 'users.delete';
}
