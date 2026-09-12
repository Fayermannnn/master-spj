<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\PermissionName;
use App\Models\TaxType;
use App\Models\User;

/**
 * @domain Cost
 *
 * Master data global — semua user boleh melihat (dipakai sebagai picker
 * di Contract/CostItem), hanya tax_types.manage (super_admin) yang boleh
 * mengubah.
 */
class TaxTypePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TaxType $taxType): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::TaxTypesManage->value);
    }

    public function update(User $user, TaxType $taxType): bool
    {
        return $user->can(PermissionName::TaxTypesManage->value);
    }

    public function delete(User $user, TaxType $taxType): bool
    {
        return $user->can(PermissionName::TaxTypesManage->value);
    }
}
