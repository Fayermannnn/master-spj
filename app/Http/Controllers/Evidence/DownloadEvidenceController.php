<?php

declare(strict_types=1);

namespace App\Http\Controllers\Evidence;

use App\Domain\Shared\Services\FileStorageService;
use App\Http\Controllers\Controller;
use App\Models\Evidence;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadEvidenceController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(Evidence $evidence, FileStorageService $fileStorage): StreamedResponse|Response
    {
        Gate::authorize('view', $evidence->project);

        return $fileStorage->download(
            $evidence->disk,
            $evidence->path,
            $evidence->original_filename,
        );
    }
}
