<?php

declare(strict_types=1);

namespace App\Http\Controllers\Personnel;

use App\Domain\Shared\Services\FileStorageService;
use App\Http\Controllers\Controller;
use App\Models\PersonnelDocument;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadPersonnelDocumentController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(PersonnelDocument $personnelDocument, FileStorageService $fileStorage): StreamedResponse|Response
    {
        Gate::authorize('view', $personnelDocument->personnel);

        return $fileStorage->download(
            $personnelDocument->disk,
            $personnelDocument->path,
            $personnelDocument->original_filename,
        );
    }
}
