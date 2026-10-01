<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivityLog;

class UserActivity
{
    public static function record(?User $user, string $action, string $details): void
    {
        if (! $user) return;
        $systemAction = isset(UserActivityLog::SYSTEM_ACTIONS[$action]);
        if (! $systemAction && (! in_array($user->role, ['user', 'researcher'], true)
            || ! trim((string) $user->department) || ! isset(UserActivityLog::ACTIONS[$action]))) return;
        $role = $user->isDepartmentDean() ? 'dean' : ($user->isResearchCoordinator() ? 'coordinator' :
            ($user->role === 'admin' ? 'admin' : ($user->role === 'user' || $user->graduation_year !== null ? 'student' : 'faculty')));
        $details = match ($action) {
            'login' => 'User logged in to the system.',
            'logout' => 'User logged out of the system.',
            default => $details,
        };

        try {
            UserActivityLog::create([
                'user_id' => $user->id,
                'department' => $user->department ?? '',
                'user_name' => $user->name,
                'role' => $role,
                'action' => $action,
                'details' => $details,
                'created_at' => now(),
            ]);
        } catch (\Throwable $error) {
            report($error);
        }
    }
}
