<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Livewire\TemplateVariables\Form;
use App\Models\Organization;
use App\Models\TemplateVariable;
use App\Models\User;
use Livewire\Livewire;

it('allows super admin to create a template variable', function (): void {
    $admin = User::factory()->create();
    $admin->syncRoles([RoleName::SuperAdmin->value]);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('key', 'custom.field')
        ->set('label', 'Custom Field')
        ->call('save')
        ->assertHasNoErrors();

    expect(TemplateVariable::query()->where('key', 'custom.field')->exists())->toBeTrue();
});

it('rejects a key with invalid characters', function (): void {
    $admin = User::factory()->create();
    $admin->syncRoles([RoleName::SuperAdmin->value]);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->set('key', 'Invalid Key!')
        ->set('label', 'Invalid')
        ->call('save')
        ->assertHasErrors(['key']);
});

it('prevents a non super-admin from creating a template variable', function (): void {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->syncRoles([RoleName::AdminPerusahaan->value]);

    Livewire::actingAs($admin)
        ->test(Form::class)
        ->assertForbidden();
});
