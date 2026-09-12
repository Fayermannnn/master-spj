<?php

declare(strict_types=1);

namespace App\Http\Controllers\Personnel;

use App\Http\Controllers\Controller;
use App\Models\PersonnelDocument;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadPersonnelDocumentController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(PersonnelDocument $personnelDocument): StreamedResponse|Response
    {
        Gate::authorize('view', $personnelDocument->personnel);

        return Storage::disk($personnelDocument->disk)->download(
            $personnelDocument->path,
            $personnelDocument->original_filename,
        );
    }
}
