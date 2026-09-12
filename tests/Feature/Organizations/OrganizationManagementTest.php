<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Livewire\Organizations\Form;
use App\Livewire\Organizations\Index;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

function makeSuperAdmin(): User
{
    $user = User::factory()->create();
    $user->syncRoles([RoleName::SuperAdmin->value]);

    return $user;
}

function makeAdminPerusahaan(Organization $organization): User
{
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->syncRoles([RoleName::AdminPerusahaan->value]);

    return $user;
}

it('allows a super admin to create an organization and records an audit log', function (): void {
    $admin = makeSuperAdmin();

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('code', 'ACME')
        ->set('name', 'PT Acme Konsultan')
        ->set('email', 'contact@acme.example')
        ->call('save')
        ->assertHasNoErrors();

    $organization = Organization::query()->where('code', 'ACME')->firstOrFail();

    expect($organization->name)->toBe('PT Acme Konsultan');
    expect(AuditLog::query()->where('module', 'Organization')->where('action', 'created')->exists())->toBeTrue();
});

it('prevents a non super-admin from creating an organization', function (): void {
    $organization = Organization::factory()->create();
    $admin = makeAdminPerusahaan($organization);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->assertForbidden();
});

it('allows admin perusahaan to update only their own organization', function (): void {
    $own = Organization::factory()->create(['name' => 'Organisasi Sendiri']);
    $other = Organization::factory()->create(['name' => 'Organisasi Lain']);
    $admin = makeAdminPerusahaan($own);

    Livewire::actingAs($admin)
        ->test(Form::class, ['organization' => $own])
        ->set('name', 'Organisasi Sendiri (Updated)')
        ->call('save')
        ->assertHasNoErrors();

    expect($own->fresh()->name)->toBe('Organisasi Sendiri (Updated)');

    Livewire::actingAs($admin)
        ->test(Form::class, ['organization' => $other])
        ->assertForbidden();
});

it('blocks deleting an organization that still has users', function (): void {
    $admin = makeSuperAdmin();
    $organization = Organization::factory()->create();
    User::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('delete', $organization->id)
        ->assertHasErrors(['delete']);

    expect(Organization::query()->find($organization->id))->not->toBeNull();
});

it('audit_logs cannot be updated or deleted', function (): void {
    $admin = makeSuperAdmin();

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('code', 'BETA')
        ->set('name', 'PT Beta')
        ->call('save');

    $log = AuditLog::query()->where('module', 'Organization')->firstOrFail();

    expect(fn () => $log->update(['module' => 'Tampered']))->toThrow(LogicException::class);
    expect(fn () => $log->delete())->toThrow(LogicException::class);
});
