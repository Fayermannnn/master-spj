<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Payment\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Payment
 *
 * Tidak punya organization_id sendiri — scoping tenant diturunkan dari
 * project (lihat PROJECT_DECISIONS.md D-014).
 */
#[Fillable([
    'project_id', 'termin_number', 'name', 'percentage', 'amount',
    'target_date', 'trigger', 'required_items', 'status',
    'submission_date', 'approval_date', 'payment_date', 'notes',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'amount' => 'decimal:2',
            'target_date' => 'date',
            'submission_date' => 'date',
            'approval_date' => 'date',
            'payment_date' => 'date',
            'status' => PaymentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
