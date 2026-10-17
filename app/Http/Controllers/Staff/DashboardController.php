<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Support\StudentRecordQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Counselor and director dashboards: headline counts for the colleges they may access.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $query = new StudentRecordQuery($user);

        if (! $user->isDirector()) {
            return view('counselor.dashboard', [
                'stats' => $query->summary(),
            ]);
        }

        $counts = $query->statusCounts();
        $statusKeys = array_keys(StudentRecordQuery::STATUSES);

        $colleges = $query->colleges()
            ->map(function ($college) use ($counts, $statusKeys) {
                $collegeCounts = $counts->where('college_id', $college->id);

                $rows = $collegeCounts->groupBy(fn ($row) => $row->year_level ?? 0)
                    ->sortKeys()
                    ->map(fn ($rows, $yearLevel) => [
                        'year_level' => $yearLevel ?: null,
                        'counts' => $this->tally($rows, $statusKeys),
                    ])
                    ->values();

                return [
                    'college' => $college,
                    'rows' => $rows,
                    'totals' => $this->tally($collegeCounts, $statusKeys),
                ];
            })
            ->values();

        $stats = $query->summary();

        return view('director.dashboard', [
            'stats' => $stats,
            'colleges' => $colleges,
            'grandTotals' => $this->tally($counts, $statusKeys),
            'withoutSata' => $query->withoutSataCount(),
            'completionRate' => $stats['total'] > 0
                ? round($stats['submitted'] / $stats['total'] * 100)
                : 0,
            'statusLabels' => StudentRecordQuery::STATUSES,
            'logs' => AccessLog::query()
                ->with('user')
                ->orderByDesc('created_at')
                ->paginate(25, ['*'], 'log_page'),
        ]);
    }

    /**
     * @param  iterable<object{record_status: string, total: int}>  $rows
     * @param  list<string>  $statusKeys
     * @return array<string, int>
     */
    private function tally(iterable $rows, array $statusKeys): array
    {
        $tally = array_fill_keys($statusKeys, 0);

        foreach ($rows as $row) {
            $tally[$row->record_status] = ($tally[$row->record_status] ?? 0) + $row->total;
        }

        return $tally + ['total' => array_sum($tally)];
    }
}
