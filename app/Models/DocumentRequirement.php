<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DocumentRequirementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain DocumentRequirement
 *
 * Master data global — project_type_id null berarti berlaku untuk semua
 * jenis project (§17 master prompt).
 */
#[Fillable(['project_type_id', 'code', 'name', 'category', 'description', 'is_active', 'sort_order'])]
class DocumentRequirement extends Model
{
    /** @use HasFactory<DocumentRequirementFactory> */
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
     * @return BelongsTo<ProjectType, $this>
     */
    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class);
    }

    /**
     * @return HasMany<RequirementRule, $this>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(RequirementRule::class);
    }

    /**
     * @return HasMany<ProjectChecklistItem, $this>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(ProjectChecklistItem::class);
    }
}
