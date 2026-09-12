<?php

declare(strict_types=1);

namespace App\Http\Controllers\Evidence;

use App\Http\Controllers\Controller;
use App\Models\Evidence;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadEvidenceController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(Evidence $evidence): StreamedResponse|Response
    {
        Gate::authorize('view', $evidence->project);

        return Storage::disk($evidence->disk)->download(
            $evidence->path,
            $evidence->original_filename,
        );
    }
}
