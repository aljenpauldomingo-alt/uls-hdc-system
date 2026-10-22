<?php

namespace App\Support;

use App\Enums\FollowUpStatus;
use App\Enums\IntakeStatus;
use App\Enums\RecordStatus;
use App\Models\User;
use App\Support\Reports\ReportFilters;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Director-only "At Risk" list: students with at least one psychological concern answered Yes on
 * their latest submitted/reviewed Form 5, or on their latest finalized Form 8 intake interview.
 *
 * The scope filters (school year, semester, college, program, year level) decide which records are
 * looked at; the other filters only narrow the list. Summary cards use the scope, not the narrowing.
 */
final class AtRiskQuery
{
    public const PER_PAGE = 25;

    /**
     * @var array<string, string>
     */
    public const SOURCES = [
        'form5' => 'Form 5',
        'form8' => 'Form 8',
        'both' => 'Both',
    ];

    /**
     * Status of the student's latest intake interview (any status).
     *
     * @var array<string, string>
     */
    public const INTAKE_FILTERS = [
        'none' => 'Not yet interviewed',
        'draft' => 'Draft',
        'finalized' => 'Finalized',
    ];

    /**
     * Query-string keys this page uses (kept in pagination and export links).
     *
     * @var list<string>
     */
    public const QUERY_KEYS = [
        'q', 'school_year', 'semester', 'college_id', 'program_id', 'year_level',
        'student_type', 'concerns', 'urgent', 'source', 'intake', 'follow_up',
    ];

    /**
     * @var Collection<int, object>|null
     */
    private ?Collection $all = null;

    /**
     * @param  array{q: string|null, student_type: string|null, concerns: list<string>, urgent: bool, source: string|null, intake: string|null, follow_up: string|null}  $filters
     */
    private function __construct(public readonly ReportFilters $scope, private readonly array $filters) {}

    /**
     * Build from the query string; unknown values are dropped silently.
     */
    public static function fromRequest(Request $request): self
    {
        $studentType = $request->string('student_type')->toString();
        $source = $request->string('source')->toString();
        $intake = $request->string('intake')->toString();
        $followUp = $request->string('follow_up')->toString();

        return new self(ReportFilters::fromRequest($request), [
            'q' => mb_substr(trim($request->string('q')->toString()), 0, 100) ?: null,
            'student_type' => array_key_exists($studentType, Form5::STUDENT_TYPES) ? $studentType : null,
            'concerns' => array_values(array_intersect((array) $request->input('concerns', []), array_keys(Form8::CONCERNS))),
            'urgent' => $request->boolean('urgent'),
            'source' => array_key_exists($source, self::SOURCES) ? $source : null,
            'intake' => array_key_exists($intake, self::INTAKE_FILTERS) ? $intake : null,
            'follow_up' => FollowUpStatus::tryFrom($followUp)?->value,
        ]);
    }

    /**
     * @return array{q: string|null, student_type: string|null, concerns: list<string>, urgent: bool, source: string|null, intake: string|null, follow_up: string|null}
     */
    public function filters(): array
    {
        return $this->filters;
    }

    /**
     * Whether any filter (scope or narrowing) is in use, for the "Clear filters" button.
     */
    public function hasFilters(): bool
    {
        return $this->scope->query() !== []
            || array_filter($this->filters, fn ($value) => $value !== null && $value !== false && $value !== []) !== [];
    }

    /**
     * At-risk students after every filter, sorted: urgent first, then most concerns, then newest.
     *
     * @return Collection<int, object>
     */
    public function results(): Collection
    {
        $filters = $this->filters;
        $search = $filters['q'] ? mb_strtolower($filters['q']) : null;

        return $this->all()->filter(function (object $row) use ($filters, $search): bool {
            $student = $row->student;

            return ($search === null || collect([$student->name, $student->last_name, $student->first_name, $student->student_number])
                ->contains(fn (?string $value) => $value !== null && str_contains(mb_strtolower($value), $search)))
                && ($filters['student_type'] === null || $row->student_type === $filters['student_type'])
                && ($filters['concerns'] === [] || array_intersect($filters['concerns'], $row->concerns) !== [])
                && (! $filters['urgent'] || $row->urgent)
                && ($filters['source'] === null || $row->source === $filters['source'])
                && ($filters['intake'] === null || ($row->intake_status?->value ?? 'none') === $filters['intake'])
                && ($filters['follow_up'] === null || $row->follow_up_status->value === $filters['follow_up']);
        })->values();
    }

    /**
     * @return LengthAwarePaginator<int, object>
     */
    public function paginate(Request $request): LengthAwarePaginator
    {
        $results = $this->results();
        $page = max(1, $request->integer('page', 1));

        return (new LengthAwarePaginator(
            $results->forPage($page, self::PER_PAGE)->values(),
            $results->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url()],
        ))->withQueryString();
    }

    /**
     * Summary cards, counted over the scope (school year, semester, college, program, year level).
     *
     * @return array{total: int, urgent: int, not_interviewed: int, follow_up_pending: int}
     */
    public function summary(): array
    {
        $all = $this->all();

        return [
            'total' => $all->count(),
            'urgent' => $all->where('urgent', true)->count(),
            'not_interviewed' => $all->whereNull('intake_status')->count(),
            'follow_up_pending' => $all->filter(fn (object $row) => $row->follow_up_status !== FollowUpStatus::Closed)->count(),
        ];
    }

    /**
     * Every at-risk student in scope, sorted.
     *
     * @return Collection<int, object>
     */
    private function all(): Collection
    {
        if ($this->all !== null) {
            return $this->all;
        }

        $form5 = $this->form5Concerns();
        $form8 = $this->form8Concerns();
        $studentIds = $form5->keys()->merge($form8->keys())->unique()->values();

        $students = User::query()
            ->whereIn('id', $studentIds)
            ->with([
                'program.college',
                'latestCumulativeRecord:cumulative_records.id,cumulative_records.user_id,cumulative_records.student_type,cumulative_records.status',
                'latestIntakeInterview:intake_interviews.id,intake_interviews.student_id,intake_interviews.status',
                'followUp.updater',
            ])
            ->get()
            ->keyBy('id');

        $urgentConcerns = Form8::followUpConcerns();

        return $this->all = $studentIds
            ->filter(fn (int $id) => $students->has($id))
            ->map(function (int $id) use ($form5, $form8, $students, $urgentConcerns): object {
                $student = $students[$id];
                $fromForm5 = $form5->get($id);
                $fromForm8 = $form8->get($id);
                $concerns = array_values(array_intersect(
                    array_keys(Form8::CONCERNS),
                    [...$fromForm5->concerns ?? [], ...$fromForm8->concerns ?? []],
                ));
                $dates = array_filter([$fromForm5?->date, $fromForm8?->date]);

                return (object) [
                    'student' => $student,
                    'record' => $student->latestCumulativeRecord,
                    'student_type' => $fromForm5->student_type ?? $student->latestCumulativeRecord?->student_type,
                    'concerns' => $concerns,
                    'urgent' => array_intersect($urgentConcerns, $concerns) !== [],
                    'source' => match (true) {
                        $fromForm5 && $fromForm8 => 'both',
                        $fromForm5 !== null => 'form5',
                        default => 'form8',
                    },
                    'intake_status' => $student->latestIntakeInterview?->status,
                    'latest_date' => $dates === [] ? null : max($dates),
                    'follow_up' => $student->followUp,
                    'follow_up_status' => $student->followUp?->status ?? FollowUpStatus::New,
                ];
            })
            ->sort(fn (object $a, object $b) => [$b->urgent, count($b->concerns), $b->latest_date?->getTimestamp() ?? 0, $a->student->last_name]
                <=> [$a->urgent, count($a->concerns), $a->latest_date?->getTimestamp() ?? 0, $b->student->last_name])
            ->values();
    }

    /**
     * Concerns answered Yes on each student's latest submitted/reviewed Form 5 in scope.
     *
     * @return Collection<int, object{concerns: list<string>, date: CarbonImmutable|null, student_type: string|null}>
     */
    private function form5Concerns(): Collection
    {
        $scope = $this->scope;
        $latestRecordIds = DB::table('cumulative_records')
            ->selectRaw('max(id)')
            ->whereIn('status', [RecordStatus::Submitted->value, RecordStatus::Reviewed->value])
            ->when($scope->schoolYear, fn ($query, $year) => $query->where('school_year', $year))
            ->when($scope->semester, fn ($query, $semester) => $query->where('semester', $semester))
            ->groupBy('user_id');

        $query = DB::table('cumulative_records as r')
            ->join('users', 'users.id', '=', 'r.user_id')
            ->join('programs', 'programs.id', '=', 'users.program_id')
            ->whereIn('r.id', $latestRecordIds)
            ->where(function ($any): void {
                foreach (array_keys(Form8::CONCERNS) as $concern) {
                    $any->orWhere("r.{$concern}", true);
                }
            })
            ->select(['r.user_id', 'r.student_type', 'r.submitted_at', ...array_map(fn (string $concern) => "r.{$concern}", array_keys(Form8::CONCERNS))]);
        $scope->applyToStudents($query);

        return $query->get()->mapWithKeys(fn (object $row) => [(int) $row->user_id => (object) [
            'concerns' => self::answeredYes($row),
            'date' => $row->submitted_at ? CarbonImmutable::parse($row->submitted_at) : null,
            'student_type' => $row->student_type,
        ]]);
    }

    /**
     * Concerns answered Yes on each student's latest finalized Form 8 in the scope's date window.
     * The latest interview is chosen first, so an older interview never lists a student.
     *
     * @return Collection<int, object{concerns: list<string>, date: CarbonImmutable|null}>
     */
    private function form8Concerns(): Collection
    {
        $query = DB::table('intake_interviews as ii')
            ->join('users', 'users.id', '=', 'ii.student_id')
            ->join('programs', 'programs.id', '=', 'users.program_id')
            ->where('ii.status', IntakeStatus::Finalized->value)
            ->select(['ii.id', 'ii.student_id', 'ii.interview_at', 'ii.finalized_at', ...array_map(fn (string $concern) => "ii.{$concern}", array_keys(Form8::CONCERNS))]);
        $this->scope->applyToStudents($query);
        $this->scope->applyWindow($query, 'ii.interview_at');

        return $query->get()
            ->groupBy('student_id')
            ->map(fn (Collection $interviews) => $interviews
                ->sortByDesc(fn (object $interview) => [(string) ($interview->interview_at ?? $interview->finalized_at), $interview->id])
                ->first())
            ->map(fn (object $latest) => (object) [
                'concerns' => self::answeredYes($latest),
                'date' => ($latest->interview_at ?? $latest->finalized_at) ? CarbonImmutable::parse($latest->interview_at ?? $latest->finalized_at) : null,
            ])
            ->filter(fn (object $latest) => $latest->concerns !== [])
            ->mapWithKeys(fn (object $latest, $studentId) => [(int) $studentId => $latest]);
    }

    /**
     * @return list<string>
     */
    private static function answeredYes(object $row): array
    {
        return array_values(array_filter(array_keys(Form8::CONCERNS), fn (string $concern) => (int) $row->{$concern} === 1));
    }
}
