<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Livewire\Users\Form;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

function seedOrganization(): Organization
{
    return Organization::factory()->create();
}

function seedAdminPerusahaan(Organization $organization): User
{
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    return $user;
}

it('lets admin perusahaan create a user scoped to their own organization', function (): void {
    $organization = seedOrganization();
    $admin = seedAdminPerusahaan($organization);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('name', 'Staff Baru')
        ->set('email', 'staff.baru@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('selectedRoles', [RoleName::Staff->value])
        ->call('save')
        ->assertHasNoErrors();

    $created = User::query()->where('email', 'staff.baru@example.com')->firstOrFail();
    expect($created->organization_id)->toBe($organization->id);
    expect($created->hasRole(RoleName::Staff->value))->toBeTrue();
});

it('never writes the password hash into the audit log', function (): void {
    $organization = seedOrganization();
    $admin = seedAdminPerusahaan($organization);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('name', 'Staff Baru')
        ->set('email', 'staff.rahasia@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('selectedRoles', [RoleName::Staff->value])
        ->call('save')
        ->assertHasNoErrors();

    $created = User::query()->where('email', 'staff.rahasia@example.com')->firstOrFail();
    $log = AuditLog::query()
        ->where('auditable_type', $created->getMorphClass())
        ->where('auditable_id', $created->id)
        ->where('action', 'created')
        ->firstOrFail();

    expect($log->after)->not->toHaveKey('password');
    expect($log->after)->not->toHaveKey('remember_token');
    expect(json_encode($log->after))->not->toContain('password123');
});

it('cannot assign the super_admin role from a non super-admin session', function (): void {
    $organization = seedOrganization();
    $admin = seedAdminPerusahaan($organization);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('name', 'Percobaan Eskalasi')
        ->set('email', 'escalate@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('selectedRoles', [RoleName::SuperAdmin->value])
        ->call('save')
        ->assertHasErrors(['selectedRoles.0']);

    expect(User::query()->where('email', 'escalate@example.com')->exists())->toBeFalse();
});

it('forces organization_id to the actor own organization even if tampered client-side', function (): void {
    $ownOrganization = seedOrganization();
    $otherOrganization = seedOrganization();
    $admin = seedAdminPerusahaan($ownOrganization);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('name', 'Tamper Test')
        ->set('email', 'tamper@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('organization_id', $otherOrganization->id)
        ->set('selectedRoles', [RoleName::Staff->value])
        ->call('save');

    $created = User::query()->where('email', 'tamper@example.com')->firstOrFail();
    expect($created->organization_id)->toBe($ownOrganization->id);
});

it('prevents a user from deleting their own account', function (): void {
    $organization = seedOrganization();
    $admin = seedAdminPerusahaan($organization);

    expect($admin->can('delete', $admin))->toBeFalse();
});

it('prevents admin perusahaan from editing a user in another organization', function (): void {
    $organization = seedOrganization();
    $otherOrganization = seedOrganization();
    $admin = seedAdminPerusahaan($organization);
    $otherUser = User::factory()->create(['organization_id' => $otherOrganization->id]);

    Livewire::actingAs($admin)
        ->test(Form::class, ['user' => $otherUser])
        ->assertForbidden();
});
