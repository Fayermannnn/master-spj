<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Workplan\Enums\WorkplanStatus;
use Database\Factories\DeliverableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @domain Workplan
 *
 * Output/keluaran project (mis. "Laporan Feasibility Study", "DED Blok
 * Plan" — lihat PROJECT_BLUEPRINT.md §3). Boleh berdiri sendiri
 * (`milestone_id` null) atau terkait satu Milestone tertentu.
 */
#[Fillable([
    'project_id', 'milestone_id', 'name', 'description', 'target_date',
    'completed_date', 'status', 'sort_order', 'created_by', 'notes',
])]
class Deliverable extends Model
{
    /** @use HasFactory<DeliverableFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'completed_date' => 'date',
            'status' => WorkplanStatus::class,
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
     * @return BelongsTo<Milestone, $this>
     */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function isOverdue(): bool
    {
        $targetDate = $this->target_date;

        return $this->status !== WorkplanStatus::Completed
            && $targetDate !== null
            && $targetDate->lt(Carbon::today());
    }
}
