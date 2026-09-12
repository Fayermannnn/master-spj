<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Domain\Workplan\Enums\WorkplanStatus;
use App\Livewire\Workplan\Manager;
use App\Models\Deliverable;
use App\Models\Milestone;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

/**
 * @return array{user: User, project: Project}
 */
function makeWorkplanProject(): array
{
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);
    $project = Project::factory()->create([
        'organization_id' => $organization->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
    ]);

    return ['user' => $user, 'project' => $project];
}

it('creates a milestone and computes its timeline position', function (): void {
    ['user' => $user, 'project' => $project] = makeWorkplanProject();

    Livewire::actingAs($user)
        ->test(Manager::class, ['project' => $project])
        ->set('milestoneName', 'Laporan Pendahuluan')
        ->set('milestoneTargetDate', '2026-04-01')
        ->call('saveMilestone')
        ->assertHasNoErrors();

    $milestone = Milestone::query()->where('project_id', $project->id)->firstOrFail();
    expect($milestone->name)->toBe('Laporan Pendahuluan');
    expect($milestone->status)->toBe(WorkplanStatus::Pending);

    $component = Livewire::actingAs($user)->test(Manager::class, ['project' => $project]);
    $position = $component->instance()->timelinePosition($milestone->target_date);
    expect($position)->toBeGreaterThan(0.0)->toBeLessThan(100.0);
});

it('marks a milestone completed and stamps the actual date', function (): void {
    ['user' => $user, 'project' => $project] = makeWorkplanProject();
    $milestone = Milestone::factory()->create(['project_id' => $project->id]);

    Livewire::actingAs($user)
        ->test(Manager::class, ['project' => $project])
        ->call('setMilestoneStatus', $milestone->id, WorkplanStatus::Completed->value)
        ->assertHasNoErrors();

    $milestone->refresh();
    expect($milestone->status)->toBe(WorkplanStatus::Completed);
    expect($milestone->actual_date)->not->toBeNull();
    expect($milestone->isOverdue())->toBeFalse();
});

it('flags a pending milestone with a past target date as overdue', function (): void {
    $milestone = Milestone::factory()->create(['target_date' => now()->subDays(3), 'status' => WorkplanStatus::Pending->value]);

    expect($milestone->isOverdue())->toBeTrue();
});

it('creates a deliverable linked to a milestone', function (): void {
    ['user' => $user, 'project' => $project] = makeWorkplanProject();
    $milestone = Milestone::factory()->create(['project_id' => $project->id]);

    Livewire::actingAs($user)
        ->test(Manager::class, ['project' => $project])
        ->set('deliverableName', 'Dokumen Feasibility Study')
        ->set('deliverableMilestoneId', $milestone->id)
        ->call('saveDeliverable')
        ->assertHasNoErrors();

    $deliverable = Deliverable::query()->where('project_id', $project->id)->firstOrFail();
    expect($deliverable->milestone_id)->toBe($milestone->id);
});

it('deletes a milestone', function (): void {
    ['user' => $user, 'project' => $project] = makeWorkplanProject();
    $milestone = Milestone::factory()->create(['project_id' => $project->id]);

    Livewire::actingAs($user)
        ->test(Manager::class, ['project' => $project])
        ->call('deleteMilestone', $milestone->id)
        ->assertHasNoErrors();

    expect(Milestone::query()->find($milestone->id))->toBeNull();
});

it('prevents a member from another organization from managing the timeline', function (): void {
    ['project' => $project] = makeWorkplanProject();

    $otherOrganization = Organization::factory()->create();
    $outsider = User::factory()->create(['organization_id' => $otherOrganization->id]);
    $outsider->syncRoles([RoleName::AdminPerusahaan->value]);

    Livewire::actingAs($outsider)
        ->test(Manager::class, ['project' => $project])
        ->assertForbidden();
});
