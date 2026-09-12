<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\PermissionName;
use App\Models\TemplateVariable;
use App\Models\User;

/**
 * @domain DocumentTemplate
 *
 * Master data global — semua user boleh melihat (dipakai untuk
 * membandingkan hasil scan placeholder), hanya template_variables.manage
 * (super_admin) yang boleh mengubah.
 */
class TemplateVariablePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TemplateVariable $templateVariable): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::TemplateVariablesManage->value);
    }

    public function update(User $user, TemplateVariable $templateVariable): bool
    {
        return $user->can(PermissionName::TemplateVariablesManage->value);
    }

    public function delete(User $user, TemplateVariable $templateVariable): bool
    {
        return $user->can(PermissionName::TemplateVariablesManage->value);
    }
}
