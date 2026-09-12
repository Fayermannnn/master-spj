<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TravelAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Personnel
 *
 * Satu perjalanan dinas seorang Personnel dalam rangka satu Project —
 * dasar pengisian otomatis Surat Perjalanan Dinas (SPPD). Satu
 * personil bisa punya banyak baris (banyak perjalanan berbeda dalam
 * satu project).
 */
#[Fillable([
    'project_id', 'personnel_id', 'destination', 'purpose',
    'departure_date', 'return_date', 'transportation_mode', 'notes', 'created_by',
])]
class TravelAssignment extends Model
{
    /** @use HasFactory<TravelAssignmentFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'return_date' => 'date',
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
     * @return BelongsTo<Personnel, $this>
     */
    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
