<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\DocumentGenerator\Contracts\PdfConverterInterface;
use App\Domain\DocumentGenerator\Services\LibreOfficePdfConverter;
use App\Domain\Identity\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PdfConverterInterface::class, LibreOfficePdfConverter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole(RoleName::SuperAdmin->value) ? true : null;
        });
    }
}
