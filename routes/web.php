<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Organizations;
use App\Livewire\Users;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/organizations', Organizations\Index::class)->name('organizations.index');
    Route::get('/organizations/create', Organizations\Form::class)->name('organizations.create');
    Route::get('/organizations/{organization}/edit', Organizations\Form::class)->name('organizations.edit');

    Route::get('/users', Users\Index::class)->name('users.index');
    Route::get('/users/create', Users\Form::class)->name('users.create');
    Route::get('/users/{user}/edit', Users\Form::class)->name('users.edit');
});
