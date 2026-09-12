<?php

declare(strict_types=1);

use App\Domain\DocumentRequirement\Enums\ChecklistStatus;
use App\Domain\DocumentRequirement\Enums\RequirementRuleField;
use App\Domain\DocumentRequirement\Enums\RequirementRuleOperator;
use App\Domain\Identity\Enums\RoleName;
use App\Livewire\ProjectChecklist\Manager;
use App\Models\DocumentRequirement;
use App\Models\Organization;
use App\Models\PersonnelAssignment;
use App\Models\Project;
use App\Models\ProjectChecklistItem;
use App\Models\User;
use Livewire\Livewire;

function makeChecklistAdmin(Organization $organization): User
{
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    return $user;
}

it('always includes a universal requirement and excludes an unmet conditional one', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeChecklistAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);

    $universal = DocumentRequirement::factory()->create(['project_type_id' => null]);
    $conditional = DocumentRequirement::factory()->create(['project_type_id' => null]);
    $conditional->rules()->create([
        'field' => RequirementRuleField::HasPersonnelAssignments->value,
        'operator' => RequirementRuleOperator::IsTrue->value,
        'is_active' => true,
    ]);

    Livewire::actingAs($admin)->test(Manager::class, ['project' => $project]);

    $items = ProjectChecklistItem::query()->where('project_id', $project->id)->pluck('document_requirement_id');

    expect($items)->toContain($universal->id);
    expect($items)->not->toContain($conditional->id);
});

it('includes a conditional requirement once its condition is met', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeChecklistAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);

    $conditional = DocumentRequirement::factory()->create(['project_type_id' => null]);
    $conditional->rules()->create([
        'field' => RequirementRuleField::HasPersonnelAssignments->value,
        'operator' => RequirementRuleOperator::IsTrue->value,
        'is_active' => true,
    ]);

    PersonnelAssignment::factory()->create(['project_id' => $project->id]);

    Livewire::actingAs($admin)->test(Manager::class, ['project' => $project]);

    $items = ProjectChecklistItem::query()->where('project_id', $project->id)->pluck('document_requirement_id');
    expect($items)->toContain($conditional->id);
});

it('allows an authorized user to mark a checklist item fulfilled', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeChecklistAdmin($organization);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $requirement = DocumentRequirement::factory()->create(['project_type_id' => null]);

    $component = Livewire::actingAs($admin)->test(Manager::class, ['project' => $project]);

    $item = ProjectChecklistItem::query()
        ->where('project_id', $project->id)
        ->where('document_requirement_id', $requirement->id)
        ->firstOrFail();

    $component->call('setStatus', $item->id, ChecklistStatus::Fulfilled->value);

    expect($item->fresh()->status)->toBe(ChecklistStatus::Fulfilled);
});
