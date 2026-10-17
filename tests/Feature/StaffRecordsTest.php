<?php

namespace Tests\Feature;

use App\Enums\RecordStatus;
use App\Models\AccessLog;
use App\Models\College;
use App\Models\CumulativeRecord;
use App\Models\Program;
use App\Models\User;
use App\Notifications\RecordSubmitted;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StaffRecordsTest extends TestCase
{
    use RefreshDatabase;

    private College $collegeA;

    private College $collegeB;

    private User $counselorA;

    private User $director;

    private User $studentA;

    private User $studentB;

    private User $studentNotStarted;

    private CumulativeRecord $recordA;

    private CumulativeRecord $recordB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collegeA = College::factory()->create(['code' => 'CIT', 'name' => 'College of Information Technology']);
        $this->collegeB = College::factory()->create(['code' => 'CON', 'name' => 'College of Nursing']);
        $programA = Program::factory()->for($this->collegeA)->create(['code' => 'BSIT']);
        $programB = Program::factory()->for($this->collegeB)->create(['code' => 'BSN']);

        $this->counselorA = User::factory()->counselor([$this->collegeA])->create();
        $this->director = User::factory()->director()->create();

        $this->studentA = User::factory()->student($programA)->create(['first_name' => 'Alma', 'last_name' => 'Aquino', 'middle_name' => null, 'year_level' => 1]);
        $this->studentB = User::factory()->student($programB)->create(['first_name' => 'Bea', 'last_name' => 'Bautista', 'middle_name' => null, 'year_level' => 2]);
        $this->studentNotStarted = User::factory()->student($programA)->create(['first_name' => 'Carlo', 'last_name' => 'Cruz', 'middle_name' => null, 'year_level' => 1]);

        $this->recordA = CumulativeRecord::factory()->for($this->studentA, 'student')->submitted()->create(['first_name' => 'Alma', 'last_name' => 'Aquino']);
        $this->recordB = CumulativeRecord::factory()->for($this->studentB, 'student')->submitted()->create(['first_name' => 'Bea', 'last_name' => 'Bautista']);
    }

    public function test_counselor_list_shows_only_assigned_colleges(): void
    {
        $this->actingAs($this->counselorA)
            ->get(route('staff.records.index'))
            ->assertOk()
            ->assertSee('Aquino')
            ->assertSee('Cruz')
            ->assertDontSee('Bautista');
    }

    public function test_counselor_cannot_widen_the_list_with_a_foreign_college_filter(): void
    {
        $this->actingAs($this->counselorA)
            ->get(route('staff.records.index', ['college_id' => $this->collegeB->id]))
            ->assertOk()
            ->assertDontSee('Bautista')
            ->assertDontSee('College of Nursing');
    }

    public function test_director_list_shows_all_colleges(): void
    {
        $this->actingAs($this->director)
            ->get(route('staff.records.index'))
            ->assertOk()
            ->assertSee('Aquino')
            ->assertSee('Bautista')
            ->assertSee('Cruz');
    }

    public function test_list_filters_by_status_search_and_year_level(): void
    {
        $this->actingAs($this->director)
            ->get(route('staff.records.index', ['status' => 'not_started']))
            ->assertSee('Cruz')->assertDontSee('Aquino')->assertDontSee('Bautista');

        $this->actingAs($this->director)
            ->get(route('staff.records.index', ['status' => 'submitted']))
            ->assertSee('Aquino')->assertSee('Bautista')->assertDontSee('Cruz');

        $this->actingAs($this->director)
            ->get(route('staff.records.index', ['q' => $this->studentB->student_number]))
            ->assertSee('Bautista')->assertDontSee('Aquino');

        $this->actingAs($this->director)
            ->get(route('staff.records.index', ['year_level' => 2]))
            ->assertSee('Bautista')->assertDontSee('Aquino');
    }

    public function test_tracker_counts_and_missing_list_respect_counselor_colleges(): void
    {
        $response = $this->actingAs($this->counselorA)->get(route('staff.tracker'));

        $response->assertOk()
            ->assertSee('College of Information Technology')
            ->assertDontSee('College of Nursing')
            ->assertSee('Cruz')        // not started, in college A
            ->assertDontSee('Aquino')  // submitted, so not in the missing list
            ->assertDontSee('Bautista');

        $grandTotals = $response->viewData('grandTotals');
        $this->assertSame(1, $grandTotals['not_started']);
        $this->assertSame(1, $grandTotals['submitted']);
        $this->assertSame(2, $grandTotals['total']);
    }

    public function test_director_tracker_counts_all_colleges(): void
    {
        $grandTotals = $this->actingAs($this->director)->get(route('staff.tracker'))->assertOk()->viewData('grandTotals');

        $this->assertSame(3, $grandTotals['total']);
        $this->assertSame(2, $grandTotals['submitted']);
    }

    public function test_counselor_can_view_records_only_in_assigned_colleges(): void
    {
        $this->actingAs($this->counselorA)->get(route('staff.records.show', $this->recordA))->assertOk()->assertSee('Aquino');
        $this->actingAs($this->counselorA)->get(route('staff.records.show', $this->recordB))->assertForbidden();
        $this->actingAs($this->counselorA)->get(route('staff.records.photo', $this->recordB))->assertForbidden();
    }

    public function test_director_can_view_any_record(): void
    {
        $this->actingAs($this->director)->get(route('staff.records.show', $this->recordA))->assertOk();
        $this->actingAs($this->director)->get(route('staff.records.show', $this->recordB))->assertOk();
    }

    public function test_viewing_a_record_creates_an_access_log(): void
    {
        $this->actingAs($this->counselorA)->get(route('staff.records.show', $this->recordA))->assertOk();

        $this->assertDatabaseHas('access_logs', [
            'user_id' => $this->counselorA->id,
            'cumulative_record_id' => $this->recordA->id,
            'action' => AccessLog::ACTION_VIEW,
            'ip' => '127.0.0.1',
        ]);
    }

    public function test_forbidden_views_are_not_logged(): void
    {
        $this->actingAs($this->counselorA)->get(route('staff.records.show', $this->recordB))->assertForbidden();

        $this->assertDatabaseCount('access_logs', 0);
    }

    public function test_students_get_403_on_staff_pages(): void
    {
        foreach ([
            route('staff.records.index'),
            route('staff.tracker'),
            route('staff.notifications.index'),
            route('staff.records.show', $this->recordA),
        ] as $url) {
            $this->actingAs($this->studentA)->get($url)->assertForbidden();
        }

        $this->actingAs($this->studentA)->post(route('staff.records.review', $this->recordA))->assertForbidden();
    }

    public function test_mark_as_reviewed_locks_student_editing(): void
    {
        $this->actingAs($this->counselorA)
            ->post(route('staff.records.review', $this->recordA))
            ->assertRedirect(route('staff.records.show', $this->recordA));

        $this->recordA->refresh();
        $this->assertSame(RecordStatus::Reviewed, $this->recordA->status);
        $this->assertSame($this->counselorA->id, $this->recordA->reviewed_by);
        $this->assertNotNull($this->recordA->reviewed_at);
        $this->assertDatabaseHas('access_logs', ['cumulative_record_id' => $this->recordA->id, 'action' => AccessLog::ACTION_REVIEW]);

        $this->actingAs($this->studentA)
            ->put(route('student.record.update', [$this->recordA, 'social']), ['hobbies' => 'Changed'])
            ->assertForbidden();
    }

    public function test_only_submitted_records_in_own_colleges_can_be_reviewed(): void
    {
        $draft = CumulativeRecord::factory()->for($this->studentNotStarted, 'student')->create();

        $this->actingAs($this->counselorA)->post(route('staff.records.review', $draft))->assertForbidden();
        $this->actingAs($this->counselorA)->post(route('staff.records.review', $this->recordB))->assertForbidden();

        $this->actingAs($this->counselorA)
            ->get(route('staff.records.show', $draft))
            ->assertOk()
            ->assertDontSee('Mark as reviewed');
    }

    public function test_submission_notifies_director_and_counselors_of_that_college_only(): void
    {
        Notification::fake();
        $counselorB = User::factory()->counselor([$this->collegeB])->create();
        $record = CumulativeRecord::factory()->for($this->studentNotStarted, 'student')->completed()->create([
            'family_view' => 'Very private family answer',
        ]);

        $this->actingAs($this->studentNotStarted)
            ->post(route('student.record.submit', $record), ['consent' => '1', 'consent_name' => 'Carlo Cruz'])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo([$this->director, $this->counselorA], RecordSubmitted::class);
        Notification::assertNotSentTo([$counselorB, $this->studentNotStarted, $this->studentA], RecordSubmitted::class);

        Notification::assertSentTo($this->director, RecordSubmitted::class, function (RecordSubmitted $notification) use ($record) {
            $data = $notification->toArray($this->director);

            return array_keys($data) === ['record_id', 'student_name', 'program', 'url', 'message']
                && $data['record_id'] === $record->id
                && $data['program'] === 'BSIT'
                && ! str_contains(json_encode($data), 'private family');
        });
    }

    public function test_submission_broadcasts_private_notifications_without_form_answers(): void
    {
        $record = CumulativeRecord::factory()
            ->for($this->studentNotStarted, 'student')
            ->completed()
            ->create(['family_view' => 'Very private family answer']);

        Event::fake([BroadcastNotificationCreated::class]);

        $this->actingAs($this->studentNotStarted)
            ->post(route('student.record.submit', $record), ['consent' => '1', 'consent_name' => 'Carlo Cruz'])
            ->assertSessionHasNoErrors();

        Event::assertDispatchedTimes(BroadcastNotificationCreated::class, 2);

        foreach ([$this->director, $this->counselorA] as $recipient) {
            Event::assertDispatched(
                BroadcastNotificationCreated::class,
                function (BroadcastNotificationCreated $event) use ($recipient): bool {
                    $payload = $event->broadcastWith();
                    $channel = $event->broadcastOn()[0];

                    return $event->notifiable->is($recipient)
                        && $channel instanceof PrivateChannel
                        && $channel->name === "private-App.Models.User.{$recipient->id}"
                        && $payload['message'] === 'Carlo Cruz (BSIT) submitted their Student Cumulative Record.'
                        && ! str_contains(json_encode($payload), 'Very private family answer');
                },
            );
        }

        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_updating_an_already_submitted_record_does_not_notify_again(): void
    {
        Notification::fake();

        $this->actingAs($this->studentA)
            ->post(route('student.record.submit', $this->recordA), ['consent' => '1', 'consent_name' => 'Alma Aquino'])
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_bell_notifications_page_and_mark_all_as_read(): void
    {
        $this->director->notify(new RecordSubmitted($this->recordA));
        $this->director->notify(new RecordSubmitted($this->recordB));

        $this->actingAs($this->director)
            ->get(route('director.dashboard'))
            ->assertSee('2 unread notifications');

        $this->actingAs($this->director)
            ->get(route('staff.notifications.index'))
            ->assertOk()
            ->assertSee('Alma Aquino (BSIT) submitted their Student Cumulative Record.')
            ->assertSee('Mark all as read');

        $notification = $this->director->unreadNotifications()->first();
        $this->actingAs($this->director)
            ->get(route('staff.notifications.open', $notification->id))
            ->assertRedirect($notification->data['url']);
        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($this->director)->post(route('staff.notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $this->director->unreadNotifications()->count());
    }

    public function test_users_cannot_open_other_users_notifications(): void
    {
        $this->director->notify(new RecordSubmitted($this->recordA));
        $notification = $this->director->notifications()->first();

        $this->actingAs($this->counselorA)
            ->get(route('staff.notifications.open', $notification->id))
            ->assertNotFound();
    }

    public function test_staff_dashboards_show_counts_for_accessible_colleges(): void
    {
        $counselorStats = $this->actingAs($this->counselorA)->get(route('counselor.dashboard'))->assertOk()->viewData('stats');
        $this->assertSame(['total' => 2, 'submitted' => 1, 'not_submitted' => 1, 'waiting_for_review' => 1], $counselorStats);

        $directorStats = $this->actingAs($this->director)->get(route('director.dashboard'))->assertOk()->viewData('stats');
        $this->assertSame(['total' => 3, 'submitted' => 2, 'not_submitted' => 1, 'waiting_for_review' => 2], $directorStats);
    }
}
