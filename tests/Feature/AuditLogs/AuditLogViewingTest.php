<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Livewire\AuditLogs\Index;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

function makeAuditLogAdmin(Organization $organization): User
{
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    return $user;
}

function makeAuditLogEntry(User $actor, string $module = 'Project', string $action = 'created'): AuditLog
{
    return AuditLog::query()->create([
        'user_id' => $actor->id,
        'module' => $module,
        'action' => $action,
        'auditable_type' => 'App\\Models\\Project',
        'auditable_id' => (string) str()->ulid(),
        'before' => null,
        'after' => ['name' => 'Contoh Project'],
        'ip_address' => '127.0.0.1',
    ]);
}

it('lets super admin see audit log entries from every organization', function (): void {
    $superAdmin = User::factory()->create();
    $superAdmin->syncRoles([RoleName::SuperAdmin->value]);

    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $actorA = makeAuditLogAdmin($orgA);
    $actorB = makeAuditLogAdmin($orgB);

    makeAuditLogEntry($actorA);
    makeAuditLogEntry($actorB);

    Livewire::actingAs($superAdmin)
        ->test(Index::class)
        ->assertSee($actorA->name)
        ->assertSee($actorB->name);
});

it('scopes admin perusahaan to entries whose actor belongs to their own organization', function (): void {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $admin = makeAuditLogAdmin($orgA);
    $otherActor = makeAuditLogAdmin($orgB);

    $ownEntry = makeAuditLogEntry($admin);
    makeAuditLogEntry($otherActor);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->assertSee($admin->name)
        ->assertDontSee($otherActor->name);

    expect(AuditLog::query()->count())->toBe(2);
    expect($ownEntry->user_id)->toBe($admin->id);
});

it('filters entries by module', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeAuditLogAdmin($organization);

    makeAuditLogEntry($admin, module: 'Project');
    makeAuditLogEntry($admin, module: 'Payment');

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('module', 'Payment')
        ->assertViewHas('logs', fn ($logs) => $logs->total() === 1);
});

it('toggles the before/after detail view for one entry', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeAuditLogAdmin($organization);
    $entry = makeAuditLogEntry($admin);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->assertDontSee('Contoh Project')
        ->call('toggleDetail', $entry->id)
        ->assertSee('Contoh Project');
});

it('prevents users without the audit_logs permission from viewing the log', function (): void {
    $organization = Organization::factory()->create();
    $staff = User::factory()->create(['organization_id' => $organization->id]);
    $staff->syncRoles([RoleName::Staff->value]);

    Livewire::actingAs($staff)
        ->test(Index::class)
        ->assertForbidden();
});
