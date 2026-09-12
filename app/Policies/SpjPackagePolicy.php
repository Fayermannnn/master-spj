<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Identity\Enums\PermissionName;
use App\Models\SpjPackage;
use App\Models\User;

/**
 * @domain Spj
 *
 * Hanya `review` — mengelola manifest (create/add/remove/submit) tetap
 * digerbangi `ProjectPolicy::update` seperti sebelumnya (D-019), TIDAK
 * digantikan oleh Policy ini. `review` sengaja permission TERPISAH
 * (`spj_packages.review`, hanya dimiliki admin_perusahaan/super_admin,
 * BUKAN project_admin) — supaya ada pemisahan peran nyata antara yang
 * MENYUSUN paket dan yang MENYETUJUI, bukan sekadar formalitas satu
 * tombol lagi untuk role yang sama (PROJECT_DECISIONS.md D-028).
 */
class SpjPackagePolicy
{
    public function review(User $user, SpjPackage $package): bool
    {
        return $user->can(PermissionName::SpjPackagesReview->value)
            && $user->organization_id === $package->project?->organization_id;
    }
}
