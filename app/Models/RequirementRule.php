<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\DocumentRequirement\Enums\RequirementRuleField;
use App\Domain\DocumentRequirement\Enums\RequirementRuleOperator;
use Database\Factories\RequirementRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @domain DocumentRequirement
 */
#[Fillable(['document_requirement_id', 'field', 'operator', 'value', 'is_active'])]
class RequirementRule extends Model
{
    /** @use HasFactory<RequirementRuleFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'field' => RequirementRuleField::class,
            'operator' => RequirementRuleOperator::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<DocumentRequirement, $this>
     */
    public function documentRequirement(): BelongsTo
    {
        return $this->belongsTo(DocumentRequirement::class);
    }
}
