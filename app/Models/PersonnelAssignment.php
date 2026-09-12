<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PersonnelAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Personnel
 *
 * Penugasan seorang Personnel ke satu Project, dengan billing unit
 * fleksibel (OB/OH/OM/LS/Hari/Jam/HM/custom — §14 master prompt) sebagai
 * string bebas, bukan enum tertutup, supaya admin bisa memakai satuan
 * apa pun tanpa perlu layar master data terpisah.
 */
#[Fillable([
    'project_id', 'personnel_id', 'role_on_project', 'quantity', 'unit',
    'unit_price', 'subtotal', 'start_date', 'end_date', 'notes',
])]
class PersonnelAssignment extends Model
{
    /** @use HasFactory<PersonnelAssignmentFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
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
}
