<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProjectTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain ProjectManagement
 *
 * Master data global (bukan per-organisasi) — hanya super_admin yang
 * dapat mengelola. Lihat RULE 40 master prompt: configuration over code.
 */
#[Fillable(['code', 'name', 'description', 'is_active'])]
class ProjectType extends Model
{
    /** @use HasFactory<ProjectTypeFactory> */
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
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<DocumentRequirement, $this>
     */
    public function documentRequirements(): HasMany
    {
        return $this->hasMany(DocumentRequirement::class);
    }
}
