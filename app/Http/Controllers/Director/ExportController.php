<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Support\StudentRecordQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV exports for the director dashboard: the overall analysis table and the user activity log.
 * Streamed so large logs never exhaust memory. Director-only (route middleware enforces role).
 */
class ExportController extends Controller
{
    public function analysis(Request $request): StreamedResponse
    {
        $query = new StudentRecordQuery($request->user());
        $counts = $query->statusCounts();
        $statusKeys = array_keys(StudentRecordQuery::STATUSES);

        $filename = 'analysis-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query, $counts, $statusKeys): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['College', 'Year level', ...array_values(StudentRecordQuery::STATUSES), 'Total']);

            foreach ($query->colleges() as $college) {
                $rows = $counts->where('college_id', $college->id);

                foreach ($rows->groupBy(fn ($row) => $row->year_level ?? 0)->sortKeys() as $yearLevel => $group) {
                    $tally = array_fill_keys($statusKeys, 0);
                    foreach ($group as $row) {
                        $tally[$row->record_status] += $row->total;
                    }
                    fputcsv($out, [
                        $college->code.' - '.$college->name,
                        $yearLevel ?: 'No year level',
                        ...array_map(fn ($k) => $tally[$k], $statusKeys),
                        array_sum($tally),
                    ]);
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function logs(Request $request): StreamedResponse
    {
        $filename = 'activity-log-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Date/Time', 'User', 'Action', 'Description', 'Record ID', 'SATA ID', 'IP']);

            AccessLog::query()
                ->with('user')
                ->orderByDesc('created_at')
                ->chunk(500, function ($logs) use ($out): void {
                    foreach ($logs as $log) {
                        fputcsv($out, [
                            $log->created_at,
                            $log->user?->name,
                            $log->action,
                            $log->description,
                            $log->cumulative_record_id,
                            $log->entrance_test_result_id,
                            $log->ip,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
