<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\DocumentRequirement\Enums\RequirementRuleField;
use App\Domain\DocumentRequirement\Enums\RequirementRuleOperator;
use App\Models\DocumentRequirement;
use App\Models\RequirementRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequirementRule>
 */
class RequirementRuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_requirement_id' => DocumentRequirement::factory(),
            'field' => RequirementRuleField::HasPersonnelAssignments->value,
            'operator' => RequirementRuleOperator::IsTrue->value,
            'value' => null,
            'is_active' => true,
        ];
    }
}
