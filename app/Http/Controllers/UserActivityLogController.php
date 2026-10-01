<?php

namespace App\Http\Controllers;

use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserActivityLogController extends Controller
{
    public function systemIndex(Request $request)
    {
        abort_unless(auth()->user()?->isGlobalAdmin(), 403);
        $actions = UserActivityLog::SYSTEM_ACTIONS;
        $roles = ['dean' => 'Department Dean', 'coordinator' => 'Research Coordinator', 'faculty' => 'Faculty', 'student' => 'Student'];
        $departments = UserActivityLog::query()->whereIn('action', array_keys($actions))
            ->where('department', '!=', '')->distinct()->orderBy('department')->pluck('department');
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(array_keys($roles))],
            'department' => ['nullable', 'string', 'max:255'],
            'year_level' => ['nullable', 'integer', 'between:1,4'],
            'action' => ['nullable', Rule::in(array_keys($actions))],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $query = UserActivityLog::query()->whereIn('action', array_keys($actions));
        foreach (['role', 'department'] as $field) {
            if ($value = $filters[$field] ?? null) $query->where($field, $value);
        }
        if (($filters['role'] ?? '') === 'student' && ($year = $filters['year_level'] ?? null)) {
            $query->whereHas('user', fn ($users) => $users->where('year_level', $year));
        }
        if ($search = trim($filters['search'] ?? '')) {
            $query->where(fn ($q) => $q->where('user_name', 'like', '%' . $search . '%')->orWhere('details', 'like', '%' . $search . '%'));
        }
        if ($action = $filters['action'] ?? null) $query->where('action', $action);
        if ($date = $filters['date'] ?? null) {
            $start = \Illuminate\Support\Carbon::parse($date, 'Asia/Manila')->startOfDay();
            $query->where('created_at', '>=', $start->copy()->timezone(config('app.timezone')))
                ->where('created_at', '<', $start->copy()->addDay()->timezone(config('app.timezone')));
        }
        $logs = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->withQueryString();
        return view('admin.activity-logs', compact('logs', 'filters', 'actions', 'roles', 'departments'));
    }

    public function index(Request $request)
    {
        $dean = auth()->user();
        abort_unless($dean?->isDepartmentDean(), 403);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(['student', 'faculty'])],
            'action' => ['nullable', Rule::in(array_keys(UserActivityLog::ACTIONS))],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $department = trim((string) $dean->department);
        $query = UserActivityLog::query()
            ->where('department', $department)
            ->whereHas('user', fn ($users) => $users
                ->where('department', $department)
                ->whereIn('role', ['user', 'researcher']));

        if ($department === '') $query->whereRaw('1 = 0');
        if ($search = trim($filters['search'] ?? '')) {
            $query->whereHas('user', fn ($users) => $users->where(fn ($q) => $q
                ->where('name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhere('student_id', 'like', '%' . $search . '%')));
        }
        foreach (['role', 'action'] as $field) {
            if ($value = $filters[$field] ?? null) $query->where($field, $value);
        }
        if ($date = $filters['date'] ?? null) $query->whereDate('created_at', $date);

        $logs = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.user-activity-logs', compact('logs', 'filters', 'department') + ['actions' => UserActivityLog::ACTIONS]);
    }
}
