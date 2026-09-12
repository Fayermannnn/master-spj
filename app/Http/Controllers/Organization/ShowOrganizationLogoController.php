<?php

declare(strict_types=1);

namespace App\Http\Controllers\Organization;

use App\Domain\Shared\Services\FileStorageService;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShowOrganizationLogoController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(Organization $organization, FileStorageService $fileStorage): StreamedResponse|Response
    {
        Gate::authorize('view', $organization);

        abort_unless($organization->logo_path !== null, 404);

        return $fileStorage->inline((string) $organization->logo_disk, $organization->logo_path);
    }
}
