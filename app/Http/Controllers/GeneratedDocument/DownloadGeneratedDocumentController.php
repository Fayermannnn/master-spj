<?php

declare(strict_types=1);

namespace App\Http\Controllers\GeneratedDocument;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadGeneratedDocumentController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(Document $document, string $type): StreamedResponse|Response
    {
        Gate::authorize('view', $document->project);

        if ($type === 'pdf' && $document->hasPdf() && $document->pdf_disk !== null && $document->pdf_path !== null) {
            return Storage::disk($document->pdf_disk)->download(
                $document->pdf_path,
                $document->pdf_original_filename ?? $document->original_filename,
            );
        }

        abort_unless($type === 'docx', 404);

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->original_filename,
        );
    }
}
