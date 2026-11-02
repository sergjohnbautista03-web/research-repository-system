<?php

namespace Tests\Feature;

use App\Mail\DepartmentAccountCreated;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DepartmentAccountEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'email', 'password', 'role'] as $column) $table->string($column);
            $table->string('student_id')->nullable();
            $table->string('department')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            foreach (['is_department_dean', 'is_research_coordinator', 'is_active', 'is_approved'] as $column) $table->boolean($column)->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
        $this->withoutMiddleware();
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password', 'role' => 'admin']));
    }

    private function payload(string $type = 'dean'): array
    {
        return ['account_type' => $type, 'firstname' => 'Serg', 'lastname' => 'Owner', 'dean_id' => '000001', 'department' => 'College of Computer Studies', 'email' => $type . '@gmail.com'];
    }

    public function test_both_account_types_receive_their_login_details(): void
    {
        Mail::fake();
        foreach (['dean', 'coordinator'] as $type) {
            $this->post(route('admin.store-admin'), $this->payload($type))->assertSessionHasNoErrors()->assertSessionHas('success');
            $account = User::where('email', $type . '@gmail.com')->firstOrFail();
            $this->assertSame(($type === 'coordinator' ? 'RC-' : 'DEAN-').'000001', $account->student_id);
            Mail::assertSent(DepartmentAccountCreated::class, function ($mail) use ($account) {
                if (! $mail->hasTo($account->email)) return false;
                $this->assertSame($account->student_id.'_Ser', $mail->initialPassword);
                $this->assertTrue(Hash::check($mail->initialPassword, $account->password));
                $mail->assertSeeInHtml($account->student_id);
                $mail->assertSeeInHtml($mail->initialPassword);
                return $mail->hasTo($account->email);
            });
        }
        Mail::assertSentCount(2);
    }

    public function test_invalid_missing_and_duplicate_email_do_not_create_accounts(): void
    {
        Mail::fake();
        foreach (['', 'invalid', 'admin@example.com'] as $email) {
            $this->post(route('admin.store-admin'), array_replace($this->payload(), ['email' => $email]))->assertSessionHasErrors('email');
        }
        $this->assertSame(1, User::count());
        Mail::assertNothingSent();
    }

    public function test_mail_failure_preserves_account_and_reports_failure(): void
    {
        Mail::shouldReceive('mailer')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        $this->post(route('admin.store-admin'), $this->payload())->assertSessionHas('success')->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['email' => 'dean@gmail.com']);
    }

    public function test_department_ids_require_exactly_six_numeric_digits(): void
    {
        Mail::fake();
        foreach (['dean', 'coordinator'] as $type) {
            foreach (['', '12345', '1234567', '12a456', '123-56', 'DEAN-000001', 'RC-000001'] as $id) {
                $this->post(route('admin.store-admin'), array_replace($this->payload($type), ['dean_id' => $id]))
                    ->assertSessionHasErrors('dean_id');
            }
        }
        $this->assertSame(1, User::count());
        Mail::assertNothingSent();
    }

    public function test_department_email_uses_faculty_gmail_validation_and_normalization(): void
    {
        Mail::fake();
        foreach (['dean', 'coordinator'] as $type) {
            foreach (['', 'invalid', 'name@gmail', 'name..test@gmail.com', 'name@yahoo.com', 'name@gmail.com.example.com'] as $email) {
                $this->post(route('admin.store-admin'), array_replace($this->payload($type), ['email' => $email]))
                    ->assertSessionHasErrors('email');
            }
            session()->forget('errors');
            $this->post(route('admin.store-admin'), array_replace($this->payload($type), ['email' => '  '.strtoupper($type).'@GMAIL.COM  ']))
                ->assertSessionHasNoErrors();
            $this->assertDatabaseHas('users', ['email' => $type.'@gmail.com']);
        }
        Mail::assertSentCount(2);
    }

    public function test_required_department_fields_have_validation_messages(): void
    {
        Mail::fake();
        foreach (['dean', 'coordinator'] as $type) {
            $this->post(route('admin.store-admin'), array_replace($this->payload($type), [
                'firstname' => '', 'lastname' => '', 'department' => '', 'dean_id' => '', 'email' => '',
            ]))->assertSessionHasErrors([
                'firstname' => 'This field is required.', 'lastname' => 'This field is required.',
                'department' => 'This field is required.', 'dean_id' => 'This field is required.',
                'email' => 'Email address is required.',
            ]);
        }
        Mail::assertNothingSent();
    }

    public function test_formatted_ids_and_normalized_emails_cannot_be_reused(): void
    {
        Mail::fake();
        foreach (['dean', 'coordinator'] as $type) {
            session()->forget('errors');
            $this->post(route('admin.store-admin'), $this->payload($type))->assertSessionHasNoErrors();
            $this->post(route('admin.store-admin'), array_replace($this->payload($type), ['email' => 'other'.$type.'@gmail.com']))
                ->assertSessionHasErrors('dean_id');
            $this->post(route('admin.store-admin'), array_replace($this->payload($type), ['dean_id' => '000002', 'email' => strtoupper($type).'@GMAIL.COM']))
                ->assertSessionHasErrors('email');
        }
        $this->assertSame(3, User::count());
        Mail::assertSentCount(2);
    }

    public function test_previously_assigned_department_ids_cannot_be_reused_after_deletion(): void
    {
        (require database_path('migrations/2026_11_01_000001_create_member_id_sequences.php'))->up();
        Mail::fake();
        foreach (['dean', 'coordinator'] as $type) {
            session()->forget('errors');
            $this->post(route('admin.store-admin'), $this->payload($type))->assertSessionHasNoErrors();
            User::where('email', $type.'@gmail.com')->firstOrFail()->delete();
            $this->post(route('admin.store-admin'), $this->payload($type))->assertSessionHasErrors('dean_id');
        }
        $this->assertSame(1, User::count());
        Mail::assertSentCount(2);
    }
}
