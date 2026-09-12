<?php

declare(strict_types=1);

use App\Domain\DocumentRequirement\Enums\RequirementRuleField;
use App\Domain\DocumentRequirement\Enums\RequirementRuleOperator;
use App\Domain\DocumentRequirement\Services\RequirementRuleEvaluator;
use App\Models\CostCategory;
use App\Models\CostItem;
use App\Models\Personnel;
use App\Models\PersonnelAssignment;
use App\Models\PersonnelCategory;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\RequirementRule;

function makeRule(RequirementRuleField $field, RequirementRuleOperator $operator, ?string $value = null): RequirementRule
{
    return RequirementRule::factory()->make([
        'field' => $field->value,
        'operator' => $operator->value,
        'value' => $value,
        'is_active' => true,
    ]);
}

it('passes with no active rules', function (): void {
    $project = Project::factory()->create();
    $evaluator = new RequirementRuleEvaluator;

    expect($evaluator->passes($project, collect()))->toBeTrue();
});

it('evaluates has_personnel_assignments correctly', function (): void {
    $project = Project::factory()->create();
    $evaluator = new RequirementRuleEvaluator;
    $rules = collect([makeRule(RequirementRuleField::HasPersonnelAssignments, RequirementRuleOperator::IsTrue)]);

    expect($evaluator->passes($project, $rules))->toBeFalse();

    PersonnelAssignment::factory()->create(['project_id' => $project->id]);

    expect($evaluator->passes($project, $rules))->toBeTrue();
});

it('evaluates has_travel_cost by checking the cost category code', function (): void {
    $project = Project::factory()->create();
    $evaluator = new RequirementRuleEvaluator;
    $rules = collect([makeRule(RequirementRuleField::HasTravelCost, RequirementRuleOperator::IsTrue)]);

    $nonTravel = CostCategory::factory()->create(['code' => 'PRINTING']);
    CostItem::factory()->create(['project_id' => $project->id, 'cost_category_id' => $nonTravel->id]);

    expect($evaluator->passes($project, $rules))->toBeFalse();

    $travel = CostCategory::factory()->create(['code' => 'TRAVEL']);
    CostItem::factory()->create(['project_id' => $project->id, 'cost_category_id' => $travel->id]);

    expect($evaluator->passes($project, $rules))->toBeTrue();
});

it('evaluates project_type_code with equals', function (): void {
    $projectType = ProjectType::factory()->create(['code' => 'SURVEY']);
    $project = Project::factory()->create(['project_type_id' => $projectType->id]);
    $evaluator = new RequirementRuleEvaluator;
    $rules = collect([makeRule(RequirementRuleField::ProjectTypeCode, RequirementRuleOperator::Equals, 'SURVEY')]);

    expect($evaluator->passes($project, $rules))->toBeTrue();

    $rulesMismatch = collect([makeRule(RequirementRuleField::ProjectTypeCode, RequirementRuleOperator::Equals, 'STUDY')]);
    expect($evaluator->passes($project, $rulesMismatch))->toBeFalse();
});

it('evaluates personnel_category_codes with contains', function (): void {
    $project = Project::factory()->create();
    $evaluator = new RequirementRuleEvaluator;
    $rules = collect([makeRule(RequirementRuleField::PersonnelCategoryCodes, RequirementRuleOperator::Contains, 'TENAGA_AHLI')]);

    expect($evaluator->passes($project, $rules))->toBeFalse();

    $category = PersonnelCategory::factory()->create(['code' => 'TENAGA_AHLI']);
    $personnel = Personnel::factory()->create(['personnel_category_id' => $category->id]);
    PersonnelAssignment::factory()->create(['project_id' => $project->id, 'personnel_id' => $personnel->id]);

    expect($evaluator->passes($project, $rules))->toBeTrue();
});

it('combines multiple active rules with AND semantics', function (): void {
    $project = Project::factory()->create();
    $evaluator = new RequirementRuleEvaluator;
    $rules = collect([
        makeRule(RequirementRuleField::HasPersonnelAssignments, RequirementRuleOperator::IsTrue),
        makeRule(RequirementRuleField::HasPayments, RequirementRuleOperator::IsTrue),
    ]);

    PersonnelAssignment::factory()->create(['project_id' => $project->id]);
    expect($evaluator->passes($project, $rules))->toBeFalse();

    $project->payments()->create([
        'termin_number' => 1,
        'name' => 'Termin 1',
        'amount' => 1000,
        'status' => 'pending',
    ]);
    expect($evaluator->passes($project, $rules))->toBeTrue();
});

it('ignores inactive rules', function (): void {
    $project = Project::factory()->create();
    $evaluator = new RequirementRuleEvaluator;
    $rule = makeRule(RequirementRuleField::HasPersonnelAssignments, RequirementRuleOperator::IsTrue);
    $rule->is_active = false;

    expect($evaluator->passes($project, collect([$rule])))->toBeTrue();
});
