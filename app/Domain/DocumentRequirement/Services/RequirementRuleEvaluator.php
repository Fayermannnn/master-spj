<?php

declare(strict_types=1);

namespace App\Domain\DocumentRequirement\Services;

use App\Domain\DocumentRequirement\Enums\RequirementRuleField;
use App\Domain\DocumentRequirement\Enums\RequirementRuleOperator;
use App\Models\Project;
use App\Models\RequirementRule;
use Illuminate\Support\Collection;

/**
 * @domain DocumentRequirement
 *
 * Evaluator kecil dan tertutup (§18 master prompt: "jangan membuat
 * expression engine yang terlalu kompleks") — hanya mengerti field dari
 * RequirementRuleField, tidak ada reflection/dot-path bebas ke model.
 * Semua rule aktif pada satu requirement digabung dengan AND; requirement
 * tanpa rule aktif dianggap selalu berlaku.
 */
class RequirementRuleEvaluator
{
    /**
     * @param  Collection<int, RequirementRule>  $rules
     */
    public function passes(Project $project, Collection $rules): bool
    {
        $activeRules = $rules->where('is_active', true);

        foreach ($activeRules as $rule) {
            if (! $this->evaluateRule($project, $rule)) {
                return false;
            }
        }

        return true;
    }

    private function evaluateRule(Project $project, RequirementRule $rule): bool
    {
        $actual = $this->resolveFieldValue($project, $rule->field);

        return $this->compare($actual, $rule->operator, $rule->value);
    }

    private function resolveFieldValue(Project $project, RequirementRuleField $field): mixed
    {
        return match ($field) {
            RequirementRuleField::ProjectTypeCode => $project->projectType?->code,
            RequirementRuleField::HasPersonnelAssignments => $project->personnelAssignments()->exists(),
            RequirementRuleField::HasPayments => $project->payments()->exists(),
            RequirementRuleField::HasTravelCost => $project->costItems()
                ->whereHas('category', fn ($query) => $query->where('code', 'TRAVEL'))
                ->exists(),
            RequirementRuleField::PersonnelCategoryCodes => $project->personnelAssignments()
                ->with('personnel.category')
                ->get()
                ->pluck('personnel.category.code')
                ->filter()
                ->unique()
                ->values()
                ->all(),
            RequirementRuleField::PaymentCount => $project->payments()->count(),
        };
    }

    private function compare(mixed $actual, RequirementRuleOperator $operator, ?string $value): bool
    {
        return match ($operator) {
            RequirementRuleOperator::IsTrue => $actual === true,
            RequirementRuleOperator::IsFalse => $actual === false,
            RequirementRuleOperator::Equals => (string) $actual === (string) $value,
            RequirementRuleOperator::NotEquals => (string) $actual !== (string) $value,
            RequirementRuleOperator::Contains => is_array($actual) && in_array($value, $actual, true),
            RequirementRuleOperator::GreaterThan => (float) $actual > (float) $value,
            RequirementRuleOperator::LessThan => (float) $actual < (float) $value,
        };
    }
}
