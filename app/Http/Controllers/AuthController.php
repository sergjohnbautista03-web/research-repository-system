<?php

namespace App\Http\Controllers;

use App\Models\CaptureAttemptLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    private function recordLoginActivity(Request $request, User $user): void
    {
        try {
            CaptureAttemptLog::create([
                'research_id' => null,
                'user_id' => $user->id,
                'event_type' => 'login_success',
                'viewer_name' => $user->name,
                'viewer_email' => $user->email,
                'viewer_department' => $user->department,
                'viewer_scope' => $user->isAdmin() ? 'admin' : 'standard',
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'details' => [
                    'login_id' => $request->input('login'),
                    'location_estimate' => $this->locationEstimateFromIp($request->ip()),
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function locationEstimateFromIp(?string $ip): string
    {
        if (! $ip) {
            return 'No IP recorded';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return 'Private/local network';
        }

        return 'Public IP recorded; location lookup not configured';
    }

    public function showLogin(Request $request)
    {
        $request->session()->reflash();

        return redirect()
            ->route('home')
            ->with('show_login_modal', true);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login'    => 'required|string',
            'password' => 'required',
        ]);

        $login = trim($credentials['login']);
        $user = User::query()
            ->where('email', $login)
            ->orWhere('student_id', $login)
            ->first();

        if ($user && ! $user->is_active) {
            return back()
                ->withErrors(['login' => 'Your account has been deactivated. Please contact the administrator.'])
                ->withInput();
        }

        if ($user && $user->role === 'user' && ! $user->is_approved) {
            return back()
                ->withErrors(['login' => 'Your student account is pending admin approval. Please wait until your Student ID is assigned.'])
                ->withInput();
        }

        if ($user && Auth::attempt(['email' => $user->email, 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();
            $authenticatedUser = Auth::user();
            $this->recordLoginActivity($request, $authenticatedUser);

            if (! $authenticatedUser->policy_accepted_at || $authenticatedUser->policy_version !== config('repository_policy.version', '2026-04-29')) {
                return redirect()->route('policy.show');
            }

            if ($authenticatedUser->isAdmin()) {
                return redirect()->route('admin.dashboard')
                    ->with('success', 'Welcome back, ' . $authenticatedUser->name . '!');
            }

            if ($authenticatedUser->role === 'user' || ($authenticatedUser->role === 'researcher' && ! $authenticatedUser->is_approved)) {
                return redirect()->route('user.dashboard')
                    ->with('success', 'Welcome back, ' . $authenticatedUser->name . '!');
            }

            return redirect()->route('home')
                ->with('success', 'Welcome back, ' . $authenticatedUser->name . '!');
        }

        return back()->withErrors(['login' => 'Invalid username or password.'])->withInput();
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }

    private function departments(): array
    {
        return [
            'College of Accountancy and Business Education',
            'College of Computer Studies',
            'College of Criminal Justice Education',
            'College of Education',
            'College of Engineering and Architecture',
            'College of Maritime Studies',
        ];
    }

}
