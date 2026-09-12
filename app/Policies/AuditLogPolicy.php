<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\PermissionName;
use App\Models\User;

/**
 * @domain AuditLog
 *
 * Hanya `viewAny` — audit log dibaca sebagai daftar (dengan scoping
 * organisasi diterapkan di query, bukan per-baris via `view()`), dan
 * append-only (RULE 6 CLAUDE.md, ditegakkan di `App\Models\AuditLog`
 * sendiri): tidak ada update/delete untuk digerbangi.
 */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::AuditLogsViewAny->value);
    }
}
