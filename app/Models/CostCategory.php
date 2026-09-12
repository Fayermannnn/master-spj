<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CostCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Cost
 *
 * Master data global (seperti ProjectType/PersonnelCategory) — §13
 * master prompt: PERSONNEL, NON_PERSONNEL, TRAVEL, dst.
 */
#[Fillable(['code', 'name', 'description', 'is_active'])]
class CostCategory extends Model
{
    /** @use HasFactory<CostCategoryFactory> */
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
     * @return HasMany<CostItem, $this>
     */
    public function costItems(): HasMany
    {
        return $this->hasMany(CostItem::class);
    }
}
