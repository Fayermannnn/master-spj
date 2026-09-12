<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Domain\Reporting\Services\ProjectSummaryExcelExporter;
use App\Domain\Reporting\Services\ProjectSummaryPdfExporter;
use App\Domain\Reporting\Services\ProjectSummaryReportService;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadProjectSummaryReportController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(
        Request $request,
        ProjectSummaryReportService $reportService,
        ProjectSummaryExcelExporter $excelExporter,
        ProjectSummaryPdfExporter $pdfExporter,
    ): BinaryFileResponse {
        Gate::authorize('viewAny', Project::class);

        /** @var User $viewer */
        $viewer = Auth::user();

        $rows = $reportService->rows([
            'organization_id' => $viewer->hasRole('super_admin') ? null : $viewer->organization_id,
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'client_id' => (string) $request->query('client_id', ''),
        ]);

        if ($request->query('format') === 'pdf') {
            $path = $pdfExporter->export($rows);

            return response()->download($path, 'laporan-ringkasan-project.pdf')->deleteFileAfterSend();
        }

        $path = $excelExporter->export($rows);

        return response()->download($path, 'laporan-ringkasan-project.xlsx')->deleteFileAfterSend();
    }
}
