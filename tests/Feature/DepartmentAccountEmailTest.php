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
        return ['account_type' => $type, 'firstname' => 'Test', 'lastname' => 'Owner', 'dean_id' => strtoupper($type) . '-001', 'department' => 'College of Computer Studies', 'email' => $type . '@gmail.com'];
    }

    public function test_both_account_types_receive_their_login_details(): void
    {
        Mail::fake();
        foreach (['dean', 'coordinator'] as $type) {
            $this->post(route('admin.store-admin'), $this->payload($type))->assertSessionHasNoErrors()->assertSessionHas('success');
            $account = User::where('email', $type . '@gmail.com')->firstOrFail();
            Mail::assertSent(DepartmentAccountCreated::class, function ($mail) use ($account) {
                if (! $mail->hasTo($account->email)) return false;
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
}
