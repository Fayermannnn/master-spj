<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PersonnelCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Personnel
 *
 * Master data global (seperti ProjectType) — hanya super_admin yang
 * mengelola. Contoh: Tenaga Ahli, Tenaga Pendukung, Surveyor, Operator,
 * Administrasi (§11 master prompt) — bukan daftar tertutup.
 */
#[Fillable(['code', 'name', 'description', 'is_active'])]
class PersonnelCategory extends Model
{
    /** @use HasFactory<PersonnelCategoryFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Personnel, $this>
     */
    public function personnel(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }
}
