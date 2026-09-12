<?php

declare(strict_types=1);

namespace App\Domain\Identity\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super_admin';
    case AdminPerusahaan = 'admin_perusahaan';
    case ProjectAdmin = 'project_admin';
    case Staff = 'staff';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::AdminPerusahaan => 'Admin Perusahaan',
            self::ProjectAdmin => 'Project Admin',
            self::Staff => 'Staff',
            self::Viewer => 'Viewer',
        };
    }
}
