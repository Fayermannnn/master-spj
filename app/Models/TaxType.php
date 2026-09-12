<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TaxTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Cost
 *
 * Master data global (§59 master prompt) — Administrator mengonfigurasi
 * jenis & tarif pajak sendiri; sistem tidak mengklaim kepatuhan pajak
 * otomatis (§60 master prompt).
 */
#[Fillable(['code', 'name', 'rate', 'is_active', 'description'])]
class TaxType extends Model
{
    /** @use HasFactory<TaxTypeFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * @return HasMany<CostItem, $this>
     */
    public function costItems(): HasMany
    {
        return $this->hasMany(CostItem::class);
    }
}
