<?php

namespace Tests\Feature;

use App\Models\Research;
use App\Models\ResearchHandoff;
use App\Models\User;
use App\Notifications\JournalWorkflowNotification;
use App\Services\JournalWorkflowNotifications;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\JournalWorkflowSchema;
use Tests\TestCase;

class JournalWorkflowEmailTest extends TestCase
{
    use JournalWorkflowSchema;

    private User $dean;

    private User $coordinator;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createJournalWorkflowSchema();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureRepositoryPolicyAccepted::class);
        Storage::fake('public');
        $this->freezeTime();
        $this->dean = $this->staff(['is_department_dean' => true]);
        $this->coordinator = $this->staff(['is_research_coordinator' => true]);
        $this->admin = $this->staff();
    }

    private function staff(array $attributes = []): User
    {
        return User::create($attributes + ['name' => 'Email Staff '.(User::count() + 1),
            'email' => 'workflow'.(User::count() + 1).'@example.test', 'password' => 'password',
            'role' => 'admin', 'department' => 'College of Computer Studies', 'is_active' => true,
            'policy_accepted_at' => now(), 'policy_version' => config('repository_policy.version')]);
    }

    private function data(): array
    {
        return ['program' => 'Information Technology', 'school_year' => '2025-2026',
            'submission_category' => Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL,
            'notes' => 'Prepare the completed journals for review.', 'deadline' => now()->addWeek()->toDateString()];
    }

    private function assign(): ResearchHandoff
    {
        $this->actingAs($this->dean)->post(route('admin.research-handoffs.store'), $this->data())
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.research-handoffs'));

        return ResearchHandoff::latest('id')->firstOrFail();
    }

    private function complete(ResearchHandoff $batch): void
    {
        $path = 'researches/email-batch-'.$batch->id.'.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4 completed journal');
        $batch->journals()->create(['title' => 'Completed <Journal> & Study',
            'authors' => ['Journal Author'], 'abstract' => 'Completed journal abstract.', 'type' => 'Quantitative',
            'submission_category' => $batch->submission_category, 'school_year' => $batch->school_year,
            'department' => $batch->department, 'coordinator_id' => $batch->coordinator_id,
            'program' => $batch->program, 'course' => $batch->program,
            'status' => Research::STATUS_DRAFT, 'file_path' => $path]);
    }

    public function test_each_workflow_stage_emails_only_its_registered_recipients_with_event_details(): void
    {
        Notification::fake();
        $secondAdmin = $this->staff();
        $others = collect([
            $this->staff(['is_research_coordinator' => true]),
            $this->staff(['is_department_dean' => true]),
            $this->staff(['is_department_dean' => true, 'department' => 'Another College']),
            $this->staff(['role' => 'user']),
            $this->staff(['is_active' => false]),
        ]);
        $batch = $this->assign();
        Notification::assertSentTo($this->coordinator, JournalWorkflowNotification::class, function ($notification, $channels) use ($batch) {
            $details = $notification->details;
            $this->assertSame(['mail'], $channels);
            $this->assertSame('assigned', $details['event']);
            $this->assertSame('Student Research Journal', $details['category']);
            $this->assertSame('2025-2026', $details['school_year']);
            $this->assertSame('Information Technology', $details['program']);
            $this->assertSame(now()->addWeek()->format('F j, Y'), $details['deadline']);
            $this->assertSame(now()->timezone('Asia/Manila')->format('F j, Y g:i A').' (Asia/Manila)', $details['date']);
            $this->assertSame(route('admin.journal-batches.show', $batch), $details['url']);
            $this->assertSame('Task Assigned', $details['status']);
            $this->assertSame(5, $notification->tries);
            $this->assertSame([60, 300, 900, 1800], $notification->backoff());
            config(['mail.default' => 'failover']);
            $this->assertSame('smtp', $notification->toMail($this->coordinator)->mailer);

            return true;
        });
        $this->assertSame($this->data()['deadline'], $batch->deadline->toDateString());
        Notification::assertCount(1);

        $this->complete($batch);
        $this->actingAs($this->coordinator)->post(route('admin.journal-batches.transition', [$batch, 'submit']))->assertRedirect();
        Notification::assertSentTo($this->dean, JournalWorkflowNotification::class, function ($notification) {
            $this->assertSame('Submitted to Dean for Review', $notification->details['status']);
            $this->assertSame(['Completed <Journal> & Study'], $notification->details['journals']);

            return $notification->details['event'] === 'submitted';
        });
        Notification::assertCount(2);
        $this->actingAs($this->dean)->post(route('admin.journal-batches.transition', [$batch, 'forward']))->assertRedirect();
        foreach ([$this->admin, $secondAdmin] as $admin) {
            Notification::assertSentTo($admin, JournalWorkflowNotification::class, fn ($notification) => $notification->details['event'] === 'forwarded' && $notification->details['status'] === 'Forwarded by Dean to Admin');
        }
        foreach ($others as $other) {
            Notification::assertNotSentTo($other, JournalWorkflowNotification::class);
        }
        Notification::assertCount(4);
    }

    public function test_html_and_text_email_render_with_direct_links_and_escape_journal_content(): void
    {
        $batch = $this->assign();
        $this->complete($batch);
        $this->actingAs($this->coordinator)->post(route('admin.journal-batches.transition', [$batch, 'submit']))->assertRedirect();
        $this->actingAs($this->dean)->post(route('admin.journal-batches.transition', [$batch, 'forward']))->assertRedirect();

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(3, $messages);
        foreach ([$this->coordinator, $this->dean, $this->admin] as $index => $recipient) {
            $message = $messages[$index]->getOriginalMessage();
            $this->assertSame($recipient->email, $message->getTo()[0]->getAddress());
            $this->assertStringContainsString('AY 2025-2026', $message->getSubject());
            foreach (['Information Technology', 'Student Research Journal', 'Deadline', route('admin.journal-batches.show', $batch)] as $value) {
                $this->assertStringContainsString($value, $message->getHtmlBody());
                $this->assertStringContainsString($value, $message->getTextBody());
            }
        }
        $this->assertStringContainsString('Completed &lt;Journal&gt; &amp; Study', $messages[1]->getOriginalMessage()->getHtmlBody());
        $this->assertStringNotContainsString('Completed <Journal>', $messages[1]->getOriginalMessage()->getHtmlBody());
    }

    public function test_invalid_unauthorized_and_repeated_workflow_requests_do_not_send_extra_emails(): void
    {
        Notification::fake();
        $this->actingAs($this->dean)->post(route('admin.research-handoffs.store'), ['deadline' => 'invalid'] + $this->data())
            ->assertSessionHasErrors('deadline');
        Notification::assertNothingSent();
        $batch = $this->assign();
        $this->actingAs($this->coordinator)->post(route('admin.journal-batches.transition', [$batch, 'submit']))->assertSessionHasErrors('batch');
        $this->actingAs($this->admin)->post(route('admin.journal-batches.transition', [$batch, 'submit']))->assertForbidden();
        Notification::assertCount(1);
        $this->complete($batch);
        foreach (['submit' => $this->coordinator, 'forward' => $this->dean] as $action => $actor) {
            $this->actingAs($actor)->post(route('admin.journal-batches.transition', [$batch, $action]))->assertRedirect();
            $this->post(route('admin.journal-batches.transition', [$batch, $action]))->assertStatus(409);
        }
        Notification::assertCount(3);
    }

    public function test_rollback_sends_nothing_and_a_committed_event_preserves_its_snapshot(): void
    {
        Notification::fake();
        $batch = $this->assign();
        Notification::fake();
        DB::beginTransaction();
        app(JournalWorkflowNotifications::class)->assigned($batch);
        Notification::assertNothingSent();
        DB::rollBack();
        Notification::assertNothingSent();

        DB::beginTransaction();
        app(JournalWorkflowNotifications::class)->assigned($batch);
        $batch->update(['school_year' => '2024-2025']);
        Notification::assertNothingSent();
        DB::commit();
        Notification::assertSentTo($this->coordinator, JournalWorkflowNotification::class,
            fn ($notification) => $notification->details['school_year'] === '2025-2026');
    }

    public function test_queued_delivery_uses_the_current_registered_email_and_rechecks_access(): void
    {
        Notification::fake();
        $batch = $this->assign();
        $notification = Notification::sent($this->coordinator, JournalWorkflowNotification::class)->sole();
        Notification::swap(new \Illuminate\Notifications\ChannelManager($this->app));
        $this->coordinator->update(['email' => 'updated-email@example.test']);
        $this->coordinator->notifyNow($notification);
        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertSame('updated-email@example.test', $messages->sole()->getOriginalMessage()->getTo()[0]->getAddress());
        $this->coordinator->update(['is_active' => false]);
        $this->coordinator->notifyNow($notification);
        $this->coordinator->update(['is_active' => true, 'department' => 'Another College']);
        $this->coordinator->notifyNow($notification);
        $this->assertCount(1, $messages);
        $this->assertFalse($notification->shouldSend($this->admin, 'mail'));
        $batch->delete();
        $this->assertFalse($notification->shouldSend($this->coordinator, 'mail'));
    }

    public function test_delivery_failure_is_retried_by_the_database_queue_without_undoing_the_task(): void
    {
        (require database_path('migrations/0001_01_01_000002_create_jobs_table.php'))->up();
        config(['queue.default' => 'database']);
        $batch = $this->assign();
        $this->assertSame(1, DB::table('jobs')->count());
        $this->coordinator->update(['email' => 'current-queued-recipient@example.test']);
        $mailManager = Mail::getFacadeRoot();
        Mail::shouldReceive('mailer')->once()->with('array')->andThrow(new \RuntimeException('Temporary SMTP outage'));
        $options = new WorkerOptions(maxTries: 5);
        $this->app['queue.worker']->runNextJob('database', 'default', $options);
        $job = DB::table('jobs')->sole();
        $this->assertSame(1, (int) $job->attempts);
        $this->assertNull($job->reserved_at);
        $this->assertSame(now()->addSeconds(60)->timestamp, (int) $job->available_at);
        $this->assertSame(Research::STATUS_DRAFT, $batch->fresh()->batch_stage);
        $this->assertSame(0, DB::table('failed_jobs')->count());

        Mail::swap($mailManager);
        $this->app->make(\Illuminate\Notifications\ChannelManager::class)->forgetDrivers();
        $this->travel(61)->seconds();
        $this->app['queue.worker']->runNextJob('database', 'default', $options);
        $this->assertSame(0, DB::table('jobs')->count());
        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $this->assertSame($this->coordinator->email, $messages->sole()->getOriginalMessage()->getTo()[0]->getAddress());
    }

    public function test_deadline_is_optional_validated_and_displayed_on_the_task(): void
    {
        Notification::fake();
        $batch = $this->assign();
        $this->actingAs($this->coordinator)->get(route('admin.journal-batches.show', $batch))->assertOk()
            ->assertSee('Deadline:')->assertSee($batch->deadline->format('M d, Y'));
        $this->actingAs($this->dean)->post(route('admin.research-handoffs.store'), ['deadline' => now()->subDay()->toDateString()] + $this->data())
            ->assertSessionHasErrors('deadline');
        $this->post(route('admin.research-handoffs.store'), ['deadline' => null] + $this->data())->assertSessionHasNoErrors();
        $last = ResearchHandoff::latest('id')->firstOrFail();
        $this->assertNull($last->deadline);
        Notification::assertSentTo($this->coordinator, JournalWorkflowNotification::class,
            fn ($notification) => $notification->details['batch_id'] === $last->id && $notification->details['deadline'] === 'Not specified');
    }

    public function test_email_link_returns_staff_to_the_batch_after_login_and_keeps_authorization(): void
    {
        Notification::fake();
        Schema::table('users', fn (Blueprint $table) => $table->string('student_id')->nullable());
        $batch = $this->assign();
        $url = route('admin.journal-batches.show', $batch);
        $this->post('/logout');
        $this->get($url)->assertRedirect(route('login'));
        $this->post('/login', ['login' => $this->coordinator->email, 'password' => 'password'])->assertRedirect($url);
        $this->get($url)->assertOk();
        $other = $this->staff(['is_research_coordinator' => true]);
        $this->actingAs($other)->get($url)->assertForbidden();
    }
}
