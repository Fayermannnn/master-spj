<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\PermissionName;
use App\Models\CostCategory;
use App\Models\User;

/**
 * @domain Cost
 *
 * Master data global — semua user boleh melihat (dipakai sebagai picker
 * di CostItem), hanya cost_categories.manage (super_admin) yang boleh
 * mengubah.
 */
class CostCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CostCategory $costCategory): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::CostCategoriesManage->value);
    }

    public function update(User $user, CostCategory $costCategory): bool
    {
        return $user->can(PermissionName::CostCategoriesManage->value);
    }

    public function delete(User $user, CostCategory $costCategory): bool
    {
        return $user->can(PermissionName::CostCategoriesManage->value);
    }
}
