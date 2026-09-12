<?php

declare(strict_types=1);

namespace App\Http\Controllers\GeneratedDocument;

use App\Domain\Shared\Services\FileStorageService;
use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadGeneratedDocumentController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(Document $document, string $type, FileStorageService $fileStorage): StreamedResponse|Response
    {
        Gate::authorize('view', $document->project);

        if ($type === 'pdf' && $document->hasPdf() && $document->pdf_disk !== null && $document->pdf_path !== null) {
            return $fileStorage->download(
                $document->pdf_disk,
                $document->pdf_path,
                $document->pdf_original_filename ?? $document->original_filename,
            );
        }

        abort_unless($type === 'docx', 404);

        return $fileStorage->download(
            $document->disk,
            $document->path,
            $document->original_filename,
        );
    }
}
