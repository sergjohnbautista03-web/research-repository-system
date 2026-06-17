<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PolicyController extends Controller
{
    public function show()
    {
        return view('policy.show', [
            'policyVersion' => config('repository_policy.version', '2026-04-29'),
        ]);
    }

    public function accept(Request $request)
    {
        $request->validate([
            'accept_policy' => ['accepted'],
        ], [
            'accept_policy.accepted' => 'You must agree to the Repository Use Policy before continuing.',
        ]);

        $user = $request->user();

        $user->forceFill([
            'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
            'policy_accepted_ip' => $request->ip(),
            'policy_accepted_user_agent' => (string) $request->userAgent(),
        ])->save();

        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('user.dashboard'))
            ->with('success', 'Repository Use Policy accepted. You may now continue.');
    }
}
