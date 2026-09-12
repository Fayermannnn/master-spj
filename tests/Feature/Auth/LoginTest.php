<?php

declare(strict_types=1);

use App\Livewire\Auth\Login;
use App\Models\User;
use Livewire\Livewire;

it('logs in with correct credentials and redirects to dashboard', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
        'is_active' => true,
    ]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'correct-password')
        ->call('authenticate')
        ->assertRedirect(route('dashboard'));

    expect(auth()->check())->toBeTrue();
    expect(auth()->id())->toBe($user->id);
});

it('rejects an incorrect password without revealing the reason', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors(['email']);

    expect(auth()->check())->toBeFalse();
});

it('rejects a deactivated user even with the correct password', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
        'is_active' => false,
    ]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'correct-password')
        ->call('authenticate')
        ->assertHasErrors(['email']);

    expect(auth()->check())->toBeFalse();
});
