<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Contract
 *
 * Detail legal/finansial kontrak — relasi 1:1 dengan Project.
 */
#[Fillable([
    'project_id', 'contract_number', 'contract_date',
    'spmk_number', 'spmk_date', 'contract_value', 'tax_type_id', 'tax_amount',
    'net_value', 'notes',
])]
class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contract_date' => 'date',
            'spmk_date' => 'date',
            'contract_value' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'net_value' => 'decimal:2',
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
     * @return BelongsTo<TaxType, $this>
     */
    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class);
    }

    /**
     * @return HasMany<ContractAddendum, $this>
     */
    public function addenda(): HasMany
    {
        return $this->hasMany(ContractAddendum::class);
    }
}
