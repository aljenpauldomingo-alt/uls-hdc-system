<?php

namespace Tests\Feature;

use App\Enums\FollowUpStatus;
use App\Models\AccessLog;
use App\Models\College;
use App\Models\CumulativeRecord;
use App\Models\IntakeInterview;
use App\Models\Program;
use App\Models\StudentFollowUp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AtRiskTest extends TestCase
{
    use RefreshDatabase;

    private User $director;

    private User $counselor;

    private User $formFive;

    private User $formEight;

    private User $both;

    private User $noConcerns;

    private User $draftOnly;

    private User $resolvedInterview;

    private College $nursing;

    protected function setUp(): void
    {
        parent::setUp();

        $it = College::factory()->create(['code' => 'CIT']);
        $this->nursing = College::factory()->create(['code' => 'CON']);
        $bsit = Program::factory()->for($it)->create(['code' => 'BSIT']);
        $bsn = Program::factory()->for($this->nursing)->create(['code' => 'BSN']);

        $this->director = User::factory()->director()->create();
        $this->counselor = User::factory()->counselor([$it, $this->nursing])->create();

        $student = fn (string $first, string $last, Program $program, int $year = 1) => User::factory()->student($program)
            ->create(['first_name' => $first, 'last_name' => $last, 'middle_name' => null, 'year_level' => $year, 'student_number' => strtoupper($last).'-01']);

        // Form 5 only: depression, Graduate School
        $this->formFive = $student('Fe', 'Fajardo', $bsit, 2);
        $this->submitted($this->formFive, ['depression' => true, 'student_type' => 'graduate_school']);

        // Form 8 only: anxiety in a finalized interview, nothing on Form 5
        $this->formEight = $student('Eli', 'Estrada', $bsit);
        $this->submitted($this->formEight);
        $this->interview($this->formEight, ['anxiety_stress' => true]);

        // Both: self-esteem on Form 5, attempted suicide on Form 8 (urgent), in Nursing
        $this->both = $student('Bea', 'Bautista', $bsn, 3);
        $this->submitted($this->both, ['self_esteem' => true]);
        $this->interview($this->both, ['attempted_suicide' => true]);

        // Never listed
        $this->noConcerns = $student('Nina', 'Navarro', $bsit);
        $this->submitted($this->noConcerns);
        $this->interview($this->noConcerns);

        $this->draftOnly = $student('Dina', 'Dizon', $bsit);
        CumulativeRecord::factory()->for($this->draftOnly, 'student')->completed()->create(['depression' => true]);

        $this->resolvedInterview = $student('Rey', 'Ramos', $bsit);
        $this->interview($this->resolvedInterview, ['depression' => true], now()->subMonth());
        $this->interview($this->resolvedInterview, [], now()->subDay());
        IntakeInterview::factory()->for($this->resolvedInterview, 'student')->create(['depression' => true]); // draft: ignored
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function submitted(User $student, array $attributes = []): CumulativeRecord
    {
        return CumulativeRecord::factory()->for($student, 'student')->submitted()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $concerns
     */
    private function interview(User $student, array $concerns = [], $at = null): IntakeInterview
    {
        return IntakeInterview::factory()->for($student, 'student')->finalized()->create([...$concerns, 'interview_at' => $at ?? now()->subDay()]);
    }

    /**
     * Student ids in the order the page lists them.
     *
     * @param  array<string, mixed>  $query
     * @return list<int>
     */
    private function listed(array $query = []): array
    {
        $response = $this->actingAs($this->director)->get(route('director.at-risk.index', $query))->assertOk();
        preg_match_all('/data-at-risk-row="(\d+)"/', $response->getContent(), $matches);

        return array_map('intval', $matches[1]);
    }

    public function test_only_the_director_can_open_the_page_and_see_the_link(): void
    {
        $student = $this->formFive;

        foreach ([$this->counselor, $student] as $user) {
            $this->actingAs($user)->get(route('director.at-risk.index'))->assertForbidden();
            $this->actingAs($user)->get(route('director.at-risk.export'))->assertForbidden();
            $this->actingAs($user)->patch(route('director.at-risk.follow-up', $student), ['status' => 'closed'])->assertForbidden();
            $this->actingAs($user)->get(route($user->dashboardRoute()))->assertOk()->assertDontSee(route('director.at-risk.index'));
        }

        $this->actingAs($this->director)->get(route('director.dashboard'))->assertOk()->assertSee(route('director.at-risk.index'));
        $this->assertDatabaseCount('student_follow_ups', 0);
    }

    public function test_lists_students_with_concerns_from_form_5_form_8_or_both(): void
    {
        $ids = $this->listed();

        $this->assertEqualsCanonicalizing([$this->formFive->id, $this->formEight->id, $this->both->id], $ids);

        $this->actingAs($this->director)->get(route('director.at-risk.index'))
            ->assertSeeText('Depression')
            ->assertSeeText('Anxiety or stress')
            ->assertSeeText('Attempted suicide')
            ->assertDontSeeText('Navarro')
            ->assertDontSeeText('Dizon')
            ->assertDontSeeText('Ramos');

        $this->assertSame([$this->formFive->id], $this->listed(['source' => 'form5']));
        $this->assertSame([$this->formEight->id], $this->listed(['source' => 'form8']));
        $this->assertSame([$this->both->id], $this->listed(['source' => 'both']));
    }

    public function test_urgent_students_come_first_then_most_concerns(): void
    {
        $this->submitted($this->formEight, ['depression' => true, 'financial' => true, 'semester' => '2nd']); // newer record: 3 concerns with Form 8

        $ids = $this->listed();

        $this->assertSame($this->both->id, $ids[0], 'Urgent first.');
        $this->assertSame($this->formEight->id, $ids[1], 'Then the most concerns.');
        $this->assertSame([$this->both->id], $this->listed(['urgent' => 1]));
    }

    public function test_each_filter_narrows_the_list(): void
    {
        $this->assertSame([$this->formFive->id], $this->listed(['q' => 'Fajardo']));
        $this->assertSame([$this->formFive->id], $this->listed(['q' => 'FAJARDO-01']));
        $this->assertSame([$this->formFive->id], $this->listed(['student_type' => 'graduate_school']));
        $this->assertEqualsCanonicalizing([$this->formFive->id, $this->formEight->id], $this->listed(['concerns' => ['depression', 'anxiety_stress']]));
        $this->assertSame([$this->both->id], $this->listed(['college_id' => $this->nursing->id]));
        $this->assertSame([$this->both->id], $this->listed(['year_level' => 3]));
        $this->assertSame([$this->formFive->id], $this->listed(['intake' => 'none']));
        $this->assertEqualsCanonicalizing([$this->formEight->id, $this->both->id], $this->listed(['intake' => 'finalized']));
        $this->assertSame([], $this->listed(['school_year' => '2025-2026']));
        $this->assertCount(3, $this->listed(['school_year' => '2026-2027', 'semester' => '1st']));

        StudentFollowUp::factory()->for($this->both, 'student')->create(['status' => FollowUpStatus::Closed]);
        $this->assertSame([$this->both->id], $this->listed(['follow_up' => 'closed']));
        $this->assertEqualsCanonicalizing([$this->formFive->id, $this->formEight->id], $this->listed(['follow_up' => 'new']));
    }

    public function test_summary_cards_and_result_count(): void
    {
        $this->actingAs($this->director)->get(route('director.at-risk.index', ['urgent' => 1]))
            ->assertOk()
            ->assertSeeInOrder(['Total at risk', '3', 'Urgent', '1', 'Not yet interviewed', '1', 'Follow-up pending', '3'])
            ->assertSeeText('1 student')
            ->assertSeeText('Clear filters');
    }

    public function test_pagination_is_25_per_page(): void
    {
        $program = Program::factory()->create();
        User::factory()->student($program)->count(26)->create()
            ->each(fn (User $student) => $this->submitted($student, ['depression' => true]));

        $this->assertCount(25, $this->listed());
        $this->assertCount(4, $this->listed(['page' => 2]));
    }

    public function test_page_views_are_logged(): void
    {
        $this->listed(['urgent' => 1]);

        $this->assertDatabaseHas('access_logs', ['user_id' => $this->director->id, 'action' => AccessLog::ACTION_VIEW_AT_RISK]);
    }

    public function test_follow_up_update_is_saved_encrypted_and_logged(): void
    {
        $this->actingAs($this->director)
            ->from(route('director.at-risk.index'))
            ->patch(route('director.at-risk.follow-up', $this->both), ['status' => 'interview_scheduled', 'note' => 'Called her mother on Monday'])
            ->assertRedirect(route('director.at-risk.index'))
            ->assertSessionHas('status');

        $followUp = $this->both->followUp()->sole();
        $this->assertSame(FollowUpStatus::InterviewScheduled, $followUp->status);
        $this->assertSame('Called her mother on Monday', $followUp->note);
        $this->assertSame($this->director->id, $followUp->updated_by);
        $this->assertNotSame('Called her mother on Monday', DB::table('student_follow_ups')->value('note'));

        $log = AccessLog::where('action', AccessLog::ACTION_UPDATE_FOLLOW_UP)->sole();
        $this->assertSame($this->director->id, $log->user_id);
        $this->assertStringContainsString('new -> interview_scheduled', $log->details);
        $this->assertStringNotContainsString('mother', $log->details);

        $this->actingAs($this->director)
            ->patch(route('director.at-risk.follow-up', $this->both), ['status' => 'bogus'])
            ->assertSessionHasErrors('status');
        $this->actingAs($this->director)
            ->patch(route('director.at-risk.follow-up', $this->director), ['status' => 'closed'])
            ->assertNotFound();
    }

    public function test_export_is_logged_and_never_includes_notes(): void
    {
        StudentFollowUp::factory()->for($this->both, 'student')->create(['note' => 'Private note text']);

        $response = $this->actingAs($this->director)->get(route('director.at-risk.export', ['source' => 'both']))->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Bautista', $csv);
        $this->assertStringContainsString('Attempted suicide', $csv);
        $this->assertStringNotContainsString('Fajardo', $csv);
        $this->assertStringNotContainsString('Private note text', $csv);
        $this->assertDatabaseHas('access_logs', ['user_id' => $this->director->id, 'action' => AccessLog::ACTION_EXPORT_AT_RISK]);
    }
}
