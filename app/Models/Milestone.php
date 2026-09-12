<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Workplan\Enums\WorkplanStatus;
use Database\Factories\MilestoneFactory;
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
 * Titik pemeriksaan pada timeline project (mis. "Laporan Pendahuluan
 * Diserahkan"). "Terlambat" dihitung dinamis lewat `isOverdue()`, bukan
 * status tersimpan — lihat WorkplanStatus.
 */
#[Fillable([
    'project_id', 'name', 'description', 'target_date', 'actual_date',
    'status', 'sort_order', 'created_by', 'notes',
])]
class Milestone extends Model
{
    /** @use HasFactory<MilestoneFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'actual_date' => 'date',
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
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Deliverable, $this>
     */
    public function deliverables(): HasMany
    {
        return $this->hasMany(Deliverable::class);
    }

    public function isOverdue(): bool
    {
        return $this->status !== WorkplanStatus::Completed && $this->target_date->lt(Carbon::today());
    }
}
