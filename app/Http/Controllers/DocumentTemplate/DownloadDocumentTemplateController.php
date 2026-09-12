<?php

declare(strict_types=1);

namespace App\Http\Controllers\DocumentTemplate;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadDocumentTemplateController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(DocumentTemplate $documentTemplate): StreamedResponse|Response
    {
        Gate::authorize('view', $documentTemplate);

        return Storage::disk($documentTemplate->disk)->download(
            $documentTemplate->path,
            $documentTemplate->original_filename,
        );
    }
}
