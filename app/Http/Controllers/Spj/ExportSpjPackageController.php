<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spj;

use App\Domain\Spj\Services\SpjExportService;
use App\Http\Controllers\Controller;
use App\Models\SpjPackage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportSpjPackageController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(SpjPackage $spjPackage, SpjExportService $service): BinaryFileResponse
    {
        Gate::authorize('view', $spjPackage->project);

        $zipPath = $service->export($spjPackage);
        $filename = Str::slug($spjPackage->name).'.zip';

        return response()->download($zipPath, $filename)->deleteFileAfterSend();
    }
}
