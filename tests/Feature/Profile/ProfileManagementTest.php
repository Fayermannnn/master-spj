<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\RoleName;
use App\Livewire\Profile\Edit;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

function makeProfileUser(): User
{
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
        'password' => Hash::make('old-password'),
    ]);
    $user->syncRoles([RoleName::Staff->value]);

    return $user;
}

it('updates the logged in user own name and email', function (): void {
    $user = makeProfileUser();

    Livewire::actingAs($user)
        ->test(Edit::class)
        ->set('name', 'Nama Baru')
        ->set('email', 'baru@example.com')
        ->call('updateProfile')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Nama Baru');
    expect($user->fresh()->email)->toBe('baru@example.com');
});

it('rejects an email already used by another user', function (): void {
    $user = makeProfileUser();
    $other = User::factory()->create(['email' => 'sudah-ada@example.com']);

    Livewire::actingAs($user)
        ->test(Edit::class)
        ->set('name', $user->name)
        ->set('email', 'sudah-ada@example.com')
        ->call('updateProfile')
        ->assertHasErrors(['email']);
});

it('changes the password when the current password is correct', function (): void {
    $user = makeProfileUser();

    Livewire::actingAs($user)
        ->test(Edit::class)
        ->set('current_password', 'old-password')
        ->set('password', 'brand-new-password')
        ->set('password_confirmation', 'brand-new-password')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('brand-new-password', $user->fresh()->password))->toBeTrue();
});

it('rejects a password change with the wrong current password', function (): void {
    $user = makeProfileUser();

    Livewire::actingAs($user)
        ->test(Edit::class)
        ->set('current_password', 'wrong-password')
        ->set('password', 'brand-new-password')
        ->set('password_confirmation', 'brand-new-password')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});

it('rejects a new password shorter than the minimum length', function (): void {
    $user = makeProfileUser();

    Livewire::actingAs($user)
        ->test(Edit::class)
        ->set('current_password', 'old-password')
        ->set('password', 'short')
        ->set('password_confirmation', 'short')
        ->call('updatePassword')
        ->assertHasErrors(['password']);
});

it('lets any authenticated user open their own profile page regardless of role', function (): void {
    $organization = Organization::factory()->create();
    $viewer = User::factory()->create(['organization_id' => $organization->id]);
    $viewer->syncRoles([RoleName::Viewer->value]);

    Livewire::actingAs($viewer)
        ->test(Edit::class)
        ->assertOk();
});
