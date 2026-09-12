<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CostItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Cost
 */
#[Fillable([
    'project_id', 'cost_category_id', 'personnel_assignment_id', 'tax_type_id',
    'description', 'quantity', 'unit', 'unit_price', 'is_tax_inclusive',
    'subtotal', 'tax_amount', 'total', 'notes',
])]
class CostItem extends Model
{
    /** @use HasFactory<CostItemFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'is_tax_inclusive' => 'boolean',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
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
     * @return BelongsTo<CostCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CostCategory::class, 'cost_category_id');
    }

    /**
     * @return BelongsTo<PersonnelAssignment, $this>
     */
    public function personnelAssignment(): BelongsTo
    {
        return $this->belongsTo(PersonnelAssignment::class);
    }

    /**
     * @return BelongsTo<TaxType, $this>
     */
    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class);
    }
}
