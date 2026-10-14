<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureRepositoryPolicyAccepted;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RepositoryPolicyTest extends TestCase
{
    public function test_visitors_can_read_the_current_policy_without_account_access(): void
    {
        $this->get(route('policy.show'))
            ->assertOk()
            ->assertSee('Version '.config('repository_policy.version'))
            ->assertSee('Research Coordinator Responsibilities')
            ->assertSee('Admin Publication Responsibilities')
            ->assertSee('Visitors may browse published entries.')
            ->assertDontSee('Researcher Submissions')
            ->assertDontSee('name="accept_policy"', false);
    }

    public function test_an_older_acceptance_requires_the_current_policy_and_shows_the_agreement(): void
    {
        $user = $this->member('2026-04-29');
        auth()->setUser($user);
        $html = view('policy.show', [
            'policyVersion' => config('repository_policy.version'),
            'errors' => new ViewErrorBag,
        ])->render();

        $this->assertStringContainsString('name="accept_policy"', $html);
        $this->assertStringContainsString('Accept and Continue', $html);
        $this->assertStringNotContainsString('<strong>Policy accepted</strong>', $html);

        $response = $this->gate($user);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('policy.show'), $response->headers->get('Location'));
    }

    public function test_current_acceptance_allows_continuing_and_displays_philippine_time(): void
    {
        $user = $this->member(config('repository_policy.version'));
        $user->policy_accepted_at = CarbonImmutable::parse('2026-10-07 00:30:00', 'UTC')
            ->setTimezone(config('app.timezone'));
        auth()->setUser($user);
        $html = view('policy.show', [
            'policyVersion' => config('repository_policy.version'),
            'errors' => new ViewErrorBag,
        ])->render();

        $this->assertStringContainsString('<strong>Policy accepted</strong>', $html);
        $this->assertStringContainsString('October 7, 2026 08:30 AM', $html);
        $this->assertStringContainsString('(Philippine time)', $html);
        $this->assertStringNotContainsString('name="accept_policy"', $html);
        $this->assertSame(200, $this->gate($user)->getStatusCode());
    }

    private function member(string $version): User
    {
        return new User([
            'name' => 'Student User',
            'role' => 'user',
            'policy_version' => $version,
            'policy_accepted_at' => now(),
        ]);
    }

    private function gate(User $user): Response
    {
        $request = Request::create('/dashboard');
        $request->setUserResolver(fn () => $user);

        return (new EnsureRepositoryPolicyAccepted)->handle($request, fn () => new Response('Allowed'));
    }
}
