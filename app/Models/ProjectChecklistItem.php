<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\DocumentRequirement\Enums\ChecklistStatus;
use Database\Factories\ProjectChecklistItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain DocumentRequirement
 */
#[Fillable(['project_id', 'document_requirement_id', 'status', 'notes'])]
class ProjectChecklistItem extends Model
{
    /** @use HasFactory<ProjectChecklistItemFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ChecklistStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<DocumentRequirement, $this>
     */
    public function documentRequirement(): BelongsTo
    {
        return $this->belongsTo(DocumentRequirement::class);
    }
}
