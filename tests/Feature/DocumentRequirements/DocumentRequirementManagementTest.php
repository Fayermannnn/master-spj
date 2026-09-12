<?php

declare(strict_types=1);

use App\Domain\DocumentRequirement\Enums\RequirementRuleField;
use App\Domain\DocumentRequirement\Enums\RequirementRuleOperator;
use App\Domain\DocumentRequirement\Services\DocumentRequirementService;
use App\Domain\Identity\Enums\RoleName;
use App\Domain\Shared\Exceptions\DomainActionException;
use App\Livewire\DocumentRequirements\Form;
use App\Models\DocumentRequirement;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectChecklistItem;
use App\Models\User;
use Livewire\Livewire;

it('allows super admin to create a document requirement with a rule', function (): void {
    $admin = User::factory()->create();
    $admin->syncRoles([RoleName::SuperAdmin->value]);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('code', 'CUSTOM_DOC')
        ->set('name', 'Custom Document')
        ->call('addRule')
        ->set('rules.0.field', RequirementRuleField::HasPayments->value)
        ->set('rules.0.operator', RequirementRuleOperator::IsTrue->value)
        ->call('save')
        ->assertHasNoErrors();

    $requirement = DocumentRequirement::query()->where('code', 'CUSTOM_DOC')->firstOrFail();
    expect($requirement->rules()->count())->toBe(1);
});

it('prevents a non super-admin from creating a document requirement', function (): void {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->syncRoles([RoleName::AdminPerusahaan->value]);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->assertForbidden();
});

it('blocks deleting a document requirement already used in a project checklist', function (): void {
    $requirement = DocumentRequirement::factory()->create();
    $project = Project::factory()->create();
    ProjectChecklistItem::factory()->create([
        'project_id' => $project->id,
        'document_requirement_id' => $requirement->id,
    ]);

    expect(fn () => app(DocumentRequirementService::class)->delete($requirement))
        ->toThrow(DomainActionException::class);
});
