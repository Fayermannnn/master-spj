<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\PermissionName;
use App\Models\PersonnelCategory;
use App\Models\User;

/**
 * @domain Personnel
 *
 * Master data global — semua user boleh melihat (dipakai sebagai picker
 * saat mengisi data Personnel), hanya personnel_categories.manage
 * (super_admin) yang boleh mengubah.
 */
class PersonnelCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PersonnelCategory $personnelCategory): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::PersonnelCategoriesManage->value);
    }

    public function update(User $user, PersonnelCategory $personnelCategory): bool
    {
        return $user->can(PermissionName::PersonnelCategoriesManage->value);
    }

    public function delete(User $user, PersonnelCategory $personnelCategory): bool
    {
        return $user->can(PermissionName::PersonnelCategoriesManage->value);
    }
}
