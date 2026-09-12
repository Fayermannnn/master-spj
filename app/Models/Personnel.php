<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PersonnelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @domain Personnel
 */
#[Fillable([
    'organization_id', 'personnel_category_id', 'name', 'position',
    'education', 'expertise', 'id_number', 'npwp', 'certificate_number',
    'certificate_expiry_date', 'phone', 'email', 'default_rate',
    'is_active', 'notes',
])]
class Personnel extends Model
{
    /** @use HasFactory<PersonnelFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * Eloquent's pluralizer turns "Personnel" into "personnels", which is
     * wrong — the table (and the word itself) stays singular.
     */
    protected $table = 'personnel';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'certificate_expiry_date' => 'date',
            'default_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<PersonnelCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PersonnelCategory::class, 'personnel_category_id');
    }

    /**
     * @return HasMany<PersonnelDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(PersonnelDocument::class);
    }

    /**
     * @return HasMany<PersonnelAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(PersonnelAssignment::class);
    }
}
