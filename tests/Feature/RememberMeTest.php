<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\JournalWorkflowSchema;
use Tests\TestCase;

class RememberMeTest extends TestCase
{
    use JournalWorkflowSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createJournalWorkflowSchema();
        Route::middleware(['web', 'auth'])->get('/_test/remember-me', fn () => response()->json([
            'id' => Auth::id(), 'remembered' => Auth::viaRemember(),
        ]));
    }

    private function account(): User
    {
        return User::create([
            'name' => 'Remember Test Admin', 'email' => 'remember-test@gmail.com', 'password' => 'TestPassword123!',
            'role' => 'admin', 'is_active' => true, 'is_approved' => true,
            'policy_accepted_at' => now(), 'policy_version' => config('repository_policy.version'),
        ]);
    }

    private function discardSession(): void
    {
        $this->app['session']->driver()->flush();
        Auth::forgetGuards();
    }

    public function test_remember_me_restores_login_with_only_the_persistent_cookie(): void
    {
        $user = $this->account();
        $name = Auth::guard()->getRecallerName();
        $response = $this->post(route('login'), ['login' => $user->email, 'password' => 'TestPassword123!', 'remember' => 'on']);
        $response->assertSessionHasNoErrors()->assertCookie($name);
        $cookie = $response->getCookie($name);
        $this->assertGreaterThan(time() + 86400, $cookie->getExpiresTime());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertNotEmpty($user->fresh()->getRememberToken());

        $this->discardSession();
        $this->withCookie($name, $cookie->getValue())->get('/_test/remember-me')
            ->assertOk()->assertJson(['id' => $user->id, 'remembered' => true]);
    }

    public function test_unchecked_remember_me_does_not_issue_a_persistent_login_cookie(): void
    {
        $user = $this->account();
        $name = Auth::guard()->getRecallerName();
        $this->post(route('login'), ['login' => $user->email, 'password' => 'TestPassword123!'])
            ->assertSessionHasNoErrors()->assertCookieMissing($name);
        $this->assertEmpty($user->fresh()->getRememberToken());
        $this->discardSession();
        $this->get('/_test/remember-me')->assertRedirect(route('login'));
    }

    public function test_logout_invalidates_the_previous_remember_cookie(): void
    {
        $user = $this->account();
        $name = Auth::guard()->getRecallerName();
        $response = $this->post(route('login'), ['login' => $user->email, 'password' => 'TestPassword123!', 'remember' => 'on']);
        $cookie = $response->getCookie($name);
        $token = $user->fresh()->getRememberToken();
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertNotSame($token, $user->fresh()->getRememberToken());

        $this->discardSession();
        $this->withCookie($name, $cookie->getValue())->get('/_test/remember-me')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
