<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\CumulativeRecord;
use App\Models\Program;
use App\Models\User;
use App\Notifications\RecordNeedsFollowUp;
use App\Notifications\RecordSubmitted;
use App\Support\Form5;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

/**
 * Form 5 "Psychological, Relationship and Coping" step (director-only per student) and the
 * Graduate School student type.
 */
class Form5ConcernsTest extends TestCase
{
    use RefreshDatabase;

    private Program $program;

    private User $student;

    private User $counselor;

    private User $director;

    protected function setUp(): void
    {
        parent::setUp();

        $college = College::factory()->create(['code' => 'CIT']);
        $this->program = Program::factory()->for($college)->create(['code' => 'BSIT']);
        $this->student = User::factory()->student($this->program)->create(['first_name' => 'Alma', 'last_name' => 'Aquino']);
        $this->counselor = User::factory()->counselor([$college])->create();
        $this->director = User::factory()->director()->create();
    }

    /**
     * Every Yes/No item answered "No", with overrides.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function answers(array $overrides = []): array
    {
        return array_merge(array_fill_keys(Form5::concernYesNoFields(), '0'), $overrides);
    }

    /**
     * A completed record whose concerns step has not been answered yet.
     */
    private function recordWithoutConcerns(): CumulativeRecord
    {
        return CumulativeRecord::factory()->for($this->student, 'student')->completed()->create([
            ...array_fill_keys(Form5::concernYesNoFields(), null),
            'completed_steps' => array_values(array_diff(Form5::formSteps(), ['concerns'])),
        ]);
    }

    /**
     * @return list<string>
     */
    private function xlsxCells(TestResponse $response): array
    {
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $rows = IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet()->toArray();
        unlink($response->baseResponse->getFile()->getPathname());

        return array_values(array_filter(array_map('strval', array_merge(...$rows)), 'strlen'));
    }

    public function test_concerns_step_comes_right_before_review(): void
    {
        $steps = array_keys(Form5::STEPS);

        $this->assertSame(['skills', 'concerns', 'review'], array_slice($steps, -3));
        $this->assertSame('Psychological, Relationship and Coping', Form5::STEPS['concerns']);

        $record = $this->recordWithoutConcerns();
        $this->assertSame('concerns', $record->nextIncompleteStep());

        $this->actingAs($this->student)
            ->put(route('student.record.update', [$record, 'concerns']), $this->answers())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('student.record.edit', [$record, 'review']));
    }

    public function test_step_renders_the_three_groups_and_the_confidentiality_note(): void
    {
        $record = $this->recordWithoutConcerns();

        $this->actingAs($this->student)->get(route('student.record.edit', [$record, 'concerns']))
            ->assertOk()
            ->assertSeeText('Psychological Concerns')
            ->assertSeeText('Relationship Concerns')
            ->assertSeeText('Coping Strategies')
            ->assertSeeText('Rape or sexual assault')
            ->assertSeeText('Your answers are confidential and are only seen by the HDC Director. They help the HDC support you.')
            ->assertSeeText('1553');
    }

    public function test_every_item_must_be_answered_and_submit_is_blocked_until_then(): void
    {
        $record = $this->recordWithoutConcerns();

        $this->actingAs($this->student)
            ->put(route('student.record.update', [$record, 'concerns']), $this->answers(['depression' => null, 'shopping' => null]))
            ->assertSessionHasErrors(['depression' => 'Please answer Yes or No.', 'shopping']);
        $this->assertFalse($record->refresh()->hasCompletedStep('concerns'));

        $this->actingAs($this->student)
            ->post(route('student.record.submit', $record), ['consent' => '1', 'consent_name' => 'Alma Aquino'])
            ->assertSessionHasErrors('record');
        $this->assertNull($record->refresh()->submitted_at);
    }

    public function test_specify_fields_are_required_only_when_answered_yes(): void
    {
        $record = $this->recordWithoutConcerns();

        $this->actingAs($this->student)
            ->put(route('student.record.update', [$record, 'concerns']), $this->answers([
                'loss_of_significant_others' => '1', 'relationship_others' => '1', 'coping_others' => '1',
            ]))
            ->assertSessionHasErrors(['concerns_specify', 'relationship_other', 'coping_other']);

        // Answered "No": the specify texts are not required, and are not kept if sent anyway.
        $this->actingAs($this->student)
            ->put(route('student.record.update', [$record, 'concerns']), $this->answers([
                'concerns_specify' => 'Should not be kept', 'coping_other' => 'Should not be kept',
            ]))
            ->assertSessionHasNoErrors();
        $this->assertNull($record->refresh()->concerns_specify);
        $this->assertNull($record->coping_other);

        $this->actingAs($this->student)
            ->put(route('student.record.update', [$record, 'concerns']), $this->answers([
                'depression' => '1', 'loss_of_significant_others' => '1', 'concerns_specify' => 'My grandmother',
            ]))
            ->assertSessionHasNoErrors();

        $record->refresh();
        $this->assertTrue($record->depression);
        $this->assertFalse($record->eating_disorder);
        $this->assertSame('My grandmother', $record->concerns_specify);
        $this->assertNotSame('My grandmother', DB::table('cumulative_records')->where('id', $record->id)->value('concerns_specify'), 'Specify text is encrypted at rest.');
    }

    public function test_only_the_director_and_the_student_see_the_answers_on_the_record_page(): void
    {
        $record = CumulativeRecord::factory()->for($this->student, 'student')->submitted()->create([
            'depression' => true, 'loss_of_significant_others' => true, 'concerns_specify' => 'Lost my grandmother',
        ]);

        $this->actingAs($this->counselor)->get(route('staff.records.show', $record))
            ->assertOk()
            ->assertSeeText('Restricted to the HDC Director.')
            ->assertDontSeeText('Lost my grandmother')
            ->assertDontSeeText('Psychological concerns answered Yes');

        $this->actingAs($this->director)->get(route('staff.records.show', $record))
            ->assertOk()
            ->assertDontSeeText('Restricted to the HDC Director.')
            ->assertSeeText('Lost my grandmother')
            ->assertSeeText('Depression');

        $this->actingAs($this->student)->get(route('student.record.show', $record))
            ->assertOk()
            ->assertSeeText('Lost my grandmother');
    }

    public function test_only_the_director_gets_the_answers_in_exports(): void
    {
        $record = CumulativeRecord::factory()->for($this->student, 'student')->submitted()->create([
            'depression' => true, 'loss_of_significant_others' => true, 'concerns_specify' => 'Lost my grandmother',
        ]);

        $counselorCells = $this->xlsxCells($this->actingAs($this->counselor)->get(route('staff.records.export', [$record, 'format' => 'xlsx'])));
        $this->assertContains('Restricted to the HDC Director.', $counselorCells);
        $this->assertNotContains('Lost my grandmother', $counselorCells);
        $this->assertNotContains('Eating disorder', $counselorCells);

        $directorCells = $this->xlsxCells($this->actingAs($this->director)->get(route('staff.records.export', [$record, 'format' => 'xlsx'])));
        $this->assertNotContains('Restricted to the HDC Director.', $directorCells);
        $this->assertContains('Lost my grandmother', $directorCells);
        $this->assertContains('Eating disorder', $directorCells);
    }

    public function test_urgent_answer_notifies_only_the_director_without_the_concern(): void
    {
        Notification::fake();
        $record = CumulativeRecord::factory()->for($this->student, 'student')->completed()->create(['attempted_suicide' => true]);

        $this->actingAs($this->student)
            ->post(route('student.record.submit', $record), ['consent' => '1', 'consent_name' => 'Alma Aquino'])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($this->director, RecordNeedsFollowUp::class, function (RecordNeedsFollowUp $notification) use ($record): bool {
            $data = $notification->toArray($this->director);

            return $data['url'] === route('staff.records.show', $record, absolute: false)
                && str_contains($data['message'], $this->student->name)
                && ! str_contains(strtolower(json_encode($data)), 'suicide');
        });
        Notification::assertNotSentTo($this->counselor, RecordNeedsFollowUp::class);
        Notification::assertSentTo($this->counselor, RecordSubmitted::class);
    }

    public function test_no_follow_up_notification_without_an_urgent_answer(): void
    {
        Notification::fake();
        $record = CumulativeRecord::factory()->for($this->student, 'student')->completed()->create(['depression' => true]);

        $this->actingAs($this->student)
            ->post(route('student.record.submit', $record), ['consent' => '1', 'consent_name' => 'Alma Aquino'])
            ->assertSessionHasNoErrors();

        Notification::assertNotSentTo($this->director, RecordNeedsFollowUp::class);
    }

    public function test_self_reported_concerns_appear_in_the_report_as_suppressed_counts(): void
    {
        CumulativeRecord::factory()->for($this->student, 'student')->submitted()->create(['depression' => true]);

        $this->actingAs($this->director)->get(route('reports.show', 'concerns'))
            ->assertOk()
            ->assertSeeText('Self-reported concerns (Form 5)')
            ->assertSeeText('<5')
            ->assertDontSeeText($this->student->last_name);
    }

    public function test_graduate_school_student_type_saves_and_filters(): void
    {
        $this->actingAs($this->student)->post(route('student.record.store'), [
            'month_of_entry' => 8,
            'school_year' => '2026-2027',
            'semester' => '1st',
            'student_type' => 'graduate_school',
        ])->assertSessionHasNoErrors();

        $this->assertSame('graduate_school', $this->student->cumulativeRecords()->sole()->student_type);

        $other = User::factory()->student($this->program)->create(['first_name' => 'Bea', 'last_name' => 'Bautista']);
        CumulativeRecord::factory()->for($other, 'student')->create(['student_type' => 'new']);

        $this->actingAs($this->director)->get(route('staff.records.index', ['student_type' => 'graduate_school']))
            ->assertOk()
            ->assertSeeText('Aquino')
            ->assertDontSeeText('Bautista');

        $this->actingAs($this->director)->get(route('student.record.show', $this->student->cumulativeRecords()->sole()))
            ->assertForbidden();
        $this->actingAs($this->student)->get(route('student.record.edit', [$this->student->cumulativeRecords()->sole(), 'entry']))
            ->assertSeeText('Graduate School');
    }
}
