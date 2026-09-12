<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Spj\Enums\SpjPackageStatus;
use Database\Factories\SpjPackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Spj
 *
 * Satu baris = satu paket SPJ untuk sebuah Project. `payment_id` nullable
 * menentukan cakupan: diisi = paket per termin, null = paket level
 * project (mis. "SPJ Akhir") — lihat PROJECT_DECISIONS.md D-019.
 */
#[Fillable([
    'project_id', 'payment_id', 'name', 'status', 'notes', 'created_by', 'finalized_at',
    'submitted_at', 'submitted_by', 'reviewed_at', 'reviewed_by', 'review_notes',
])]
class SpjPackage extends Model
{
    /** @use HasFactory<SpjPackageFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SpjPackageStatus::class,
            'finalized_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
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
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<SpjItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SpjItem::class);
    }

    public function isProjectLevel(): bool
    {
        return $this->payment_id === null;
    }
}
