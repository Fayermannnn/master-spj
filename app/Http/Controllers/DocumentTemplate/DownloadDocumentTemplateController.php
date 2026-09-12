<?php

declare(strict_types=1);

namespace App\Http\Controllers\DocumentTemplate;

use App\Domain\Shared\Services\FileStorageService;
use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadDocumentTemplateController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(DocumentTemplate $documentTemplate, FileStorageService $fileStorage): StreamedResponse|Response
    {
        Gate::authorize('view', $documentTemplate);

        return $fileStorage->download(
            $documentTemplate->disk,
            $documentTemplate->path,
            $documentTemplate->original_filename,
        );
    }
}
