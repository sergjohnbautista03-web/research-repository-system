<?php

namespace App\Http\Controllers;

use App\Models\DepartmentAccessKey;
use App\Models\Message;
use App\Models\CaptureAttemptLog;
use App\Models\CaptureLogRead;
use App\Models\Research;
use App\Models\Semester;
use App\Models\SemesterEnrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class AdminController extends Controller
{
    private const MIN_RESEARCH_YEAR = 2022;
    private const MAX_RESEARCH_YEAR = 2026;
    private const SEMESTER_OPTIONS = [
        Semester::FIRST_SEMESTER,
        Semester::SECOND_SEMESTER,
    ];
    private const ANALYTICS_DETAIL_PAPER_LIMIT = 25;
    private const REPORTS_PER_PAGE = 50;

    private function currentAdmin(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    private function canManageSemesters(): bool
    {
        $admin = $this->currentAdmin();

        return $admin->isGlobalAdmin() || $admin->canImportUsers();
    }

    private function isDepartmentScoped(): bool
    {
        $admin = $this->currentAdmin();

        return $admin->isDepartmentDean() && ! empty($admin->department);
    }

    private function adminDepartment(): ?string
    {
        return $this->isDepartmentScoped() ? $this->currentAdmin()->department : null;
    }

    private function scopeResearchQuery($query)
    {
        if ($department = $this->adminDepartment()) {
            $query->where('department', $department);
        }

        return $query;
    }

    private function scopeCaptureLogQuery($query)
    {
        if ($department = $this->adminDepartment()) {
            $query->where(function ($inner) use ($department) {
                $inner->where('viewer_department', $department)
                    ->orWhereHas('research', fn ($researchQuery) => $researchQuery->where('department', $department));
            });
        }

        return $query;
    }

    private function scopeUserQuery($query)
    {
        if ($department = $this->adminDepartment()) {
            $query->where('department', $department);
        }

        return $query;
    }

    private function ensureResearchAccess(Research $research): void
    {
        if ($this->isDepartmentScoped() && $research->department !== $this->adminDepartment()) {
            abort(403, 'You can only access researches from your assigned department.');
        }
    }

    private function ensureResearchIsWritable(Research $research): void
    {
        $research->loadMissing('semester');

        abort_if(
            $research->semester?->isArchived(),
            403,
            'Research linked to a closed semester is read-only.'
        );
    }

    private function normalizeSchoolYear(?string $schoolYear): ?string
    {
        $schoolYear = trim((string) $schoolYear);

        if ($schoolYear === '') {
            return null;
        }

        $parts = preg_split('/\D+/', $schoolYear, -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) !== 2 || strlen($parts[0]) !== 4 || strlen($parts[1]) !== 4) {
            return null;
        }

        $startYear = (int) $parts[0];
        $endYear = (int) $parts[1];

        if ($startYear < 2000 || $endYear !== $startYear + 1) {
            return null;
        }

        return sprintf('%04d-%04d', $startYear, $endYear);
    }

    private function selectedSemester(?string $semester): ?string
    {
        return in_array($semester, self::SEMESTER_OPTIONS, true) ? $semester : null;
    }

    private function semesterOptions(): array
    {
        $this->closeExpiredSemesters();

        $semesterQuery = Semester::query();

        return [
            'semesterOptions' => self::SEMESTER_OPTIONS,
            'schoolYears' => (clone $semesterQuery)
                ->whereNotNull('school_year')
                ->distinct()
                ->orderByDesc('school_year')
                ->pluck('school_year'),
            'activeSemesters' => Semester::open()
                ->orderByDesc('school_year')
                ->orderBy('semester')
                ->get(),
        ];
    }

    private function applySemesterFilter($query, ?string $schoolYear, ?string $semester, string $relation = 'semesters'): void
    {
        if (! $schoolYear && ! $semester) {
            return;
        }

        $query->whereHas($relation, function ($semesterQuery) use ($schoolYear, $semester) {
            $semesterQuery
                ->when($schoolYear, fn ($inner) => $inner->where('school_year', $schoolYear))
                ->when($semester, fn ($inner) => $inner->where('semester', $semester));
        });
    }

    private function closeExpiredSemesters(): void
    {
        $expiredIds = Semester::query()
            ->where('is_active', true)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', now()->toDateString())
            ->pluck('id');

        if ($expiredIds->isEmpty()) {
            return;
        }

        Semester::query()
            ->whereIn('id', $expiredIds)
            ->update([
                'is_active' => false,
                'closed_at' => now(),
            ]);

        SemesterEnrollment::query()
            ->whereIn('semester_id', $expiredIds)
            ->where('status', SemesterEnrollment::STATUS_ACTIVE)
            ->update([
                'status' => SemesterEnrollment::STATUS_ARCHIVED,
                'updated_at' => now(),
            ]);
    }

    private function semesterHasResearchRecords(Semester $semester): bool
    {
        return $semester->researches()->withTrashed()->exists();
    }

    private function activeSemester(): ?Semester
    {
        $this->closeExpiredSemesters();

        return Semester::open()
            ->orderByDesc('school_year')
            ->orderByDesc('end_date')
            ->orderByDesc('semester')
            ->first();
    }

    private function ensureUserAccess(User $user): void
    {
        if (! $this->isDepartmentScoped()) {
            return;
        }

        if ($user->role === 'admin' || $user->department !== $this->adminDepartment()) {
            abort(403, 'You can only access users from your assigned department.');
        }
    }

    public function dashboard()
    {
        $researchBaseQuery = $this->scopeResearchQuery(Research::query());
        $approvedResearchBaseQuery = $this->scopeResearchQuery(Research::approved());
        $userBaseQuery = $this->scopeUserQuery(User::query()->where('role', '!=', 'admin'));
        $researcherBaseQuery = $this->scopeUserQuery(User::query()->where('role', 'researcher'));
        $hasCitationCopyCount = Schema::hasColumn('researches', 'citation_copy_count');

        $stats = [
            'total_researches' => (clone $researchBaseQuery)->count(),
            'pending'          => (clone $researchBaseQuery)->where('status', 'pending')->count(),
            'approved'         => (clone $approvedResearchBaseQuery)->count(),
            'rejected'         => (clone $researchBaseQuery)->where('status', 'rejected')->count(),
            'total_users'      => (clone $userBaseQuery)->count(),
            'total_views'      => (clone $researchBaseQuery)->sum('view_count'),
            'total_copy_citations' => $hasCitationCopyCount
                ? (clone $researchBaseQuery)->sum('citation_copy_count')
                : 0,
            'total_researchers' => (clone $researcherBaseQuery)->count(),
        ];

        $topResearches     = $this->scopeResearchQuery(Research::approved())->orderBy('view_count', 'desc')->take(5)->get();

        $analytics = $this->buildResearchAnalyticsPayload($hasCitationCopyCount);

        return view('admin.dashboard', compact(
            'stats',
            'topResearches',
        ) + $analytics);
    }

    private function buildResearchAnalyticsPayload(bool $hasCitationCopyCount): array
    {
        $years = collect(range(self::MIN_RESEARCH_YEAR, self::MAX_RESEARCH_YEAR));
        $palette = ['#6d28d9', '#0f766e', '#b45309', '#2563eb', '#be123c', '#4f46e5', '#15803d', '#9333ea'];
        $fixedDepartments = $this->isDepartmentScoped()
            ? collect([$this->adminDepartment()])
            : collect($this->departments());

        $analyticsBaseQuery = $this->scopeResearchQuery(
            Research::approved()
                ->whereBetween('year_published', [self::MIN_RESEARCH_YEAR, self::MAX_RESEARCH_YEAR])
        );

        $dataDepartments = (clone $analyticsBaseQuery)
            ->distinct()
            ->pluck('department')
            ->map(fn ($department) => $this->normalizedDepartmentName($department))
            ->unique()
            ->values();

        $departments = $fixedDepartments
            ->merge($dataDepartments)
            ->filter()
            ->unique()
            ->values();

        $departmentMeta = $departments
            ->values()
            ->map(function ($department, $index) use ($palette) {
                return [
                    'name' => $department,
                    'code' => $this->departmentCode($department),
                    'color' => $palette[$index % count($palette)],
                ];
            });

        $analyticsTotals = (clone $analyticsBaseQuery)
            ->selectRaw('department')
            ->selectRaw('year_published')
            ->selectRaw('COUNT(*) as paper_count')
            ->selectRaw('COALESCE(SUM(view_count), 0) as views')
            ->when(
                $hasCitationCopyCount,
                fn ($query) => $query->selectRaw('COALESCE(SUM(citation_copy_count), 0) as citation_copies'),
                fn ($query) => $query->selectRaw('0 as citation_copies')
            )
            ->groupBy('department', 'year_published')
            ->get()
            ->groupBy(fn ($row) => $this->normalizedDepartmentName($row->department) . '|' . $row->year_published)
            ->map(function ($rows) {
                $first = $rows->first();

                return (object) [
                    'department_name' => $this->normalizedDepartmentName($first?->department),
                    'year_published' => (int) ($first?->year_published ?? 0),
                    'paper_count' => (int) $rows->sum('paper_count'),
                    'views' => (int) $rows->sum('views'),
                    'citation_copies' => (int) $rows->sum('citation_copies'),
                ];
            });

        $analyticsChartMaxViews = 0;
        $analyticsChartData = $years->map(function ($year) use ($departmentMeta, $analyticsTotals, &$analyticsChartMaxViews, $hasCitationCopyCount) {
            return [
                'year' => $year,
                'departments' => $departmentMeta->map(function ($department) use ($year, $analyticsTotals, &$analyticsChartMaxViews, $hasCitationCopyCount) {
                    $totals = $analyticsTotals->get($department['name'] . '|' . $year);
                    $views = (int) ($totals?->views ?? 0);
                    $citationCopies = $hasCitationCopyCount ? (int) ($totals?->citation_copies ?? 0) : 0;
                    $paperCount = (int) ($totals?->paper_count ?? 0);
                    $analyticsChartMaxViews = max($analyticsChartMaxViews, $views);

                    return [
                        'department' => $department['name'],
                        'code' => $department['code'],
                        'color' => $department['color'],
                        'year' => $year,
                        'views' => $views,
                        'citation_copies' => $citationCopies,
                        'paper_count' => $paperCount,
                        'papers' => $paperCount > 0
                            ? $this->analyticsDetailPapers($department['name'], (int) $year, $hasCitationCopyCount)
                            : collect(),
                    ];
                })->values(),
            ];
        })->values();

        $analyticsChartMaxViews = max(1, $analyticsChartMaxViews);
        $analyticsYAxisLabels = collect([1, 0.75, 0.5, 0.25, 0])
            ->map(fn ($ratio) => (int) round($analyticsChartMaxViews * $ratio))
            ->values();

        $analyticsSummary = [
            'total_research_papers' => $this->scopeResearchQuery(Research::query())->count(),
            'total_views' => $this->scopeResearchQuery(Research::query())->sum('view_count'),
            'total_copy_citations' => $hasCitationCopyCount
                ? $this->scopeResearchQuery(Research::query())->sum('citation_copy_count')
                : 0,
            'total_researchers' => $this->scopeUserQuery(User::query()->where('role', 'researcher'))->count(),
            'report_scope' => $this->adminDepartment() ?: 'All Departments',
            'year_range' => self::MIN_RESEARCH_YEAR . '-' . self::MAX_RESEARCH_YEAR,
            'generated_at' => now('Asia/Manila')->format('F d, Y h:i A'),
        ];

        return [
            'analyticsYears' => $years,
            'analyticsDepartments' => $departmentMeta,
            'analyticsChartData' => $analyticsChartData,
            'analyticsChartMaxViews' => $analyticsChartMaxViews,
            'analyticsYAxisLabels' => $analyticsYAxisLabels,
            'analyticsSummary' => $analyticsSummary,
        ];
    }

    private function analyticsDetailPapers(string $department, int $year, bool $hasCitationCopyCount)
    {
        $columns = [
            'id',
            'title',
            'author_name',
            'type',
            'department',
            'year_published',
            'view_count',
            'created_at',
        ];

        if ($hasCitationCopyCount) {
            $columns[] = 'citation_copy_count';
        }

        $query = $this->scopeResearchQuery(
            Research::approved()
                ->select($columns)
                ->where('year_published', $year)
        );

        if ($department === 'Unassigned Department') {
            $query->where(function ($inner) {
                $inner->whereNull('department')
                    ->orWhereRaw("TRIM(department) = ''");
            });
        } else {
            $query->where('department', $department);
        }

        return $query
            ->orderByDesc('view_count')
            ->orderByDesc('created_at')
            ->limit(self::ANALYTICS_DETAIL_PAPER_LIMIT)
            ->get()
            ->map(fn ($research) => [
                'title' => $research->title ?: 'Untitled research',
                'author' => $research->author_name ?: 'Unknown author',
                'type' => $research->getTypeLabel(),
                'views' => (int) $research->view_count,
                'citation_copies' => $hasCitationCopyCount ? (int) ($research->citation_copy_count ?? 0) : 0,
                'url' => route('admin.research.show', $research),
            ])
            ->values();
    }

    private function normalizedDepartmentName(?string $department): string
    {
        $department = trim((string) $department);

        return $department !== '' ? $department : 'Unassigned Department';
    }

    private function departmentCode(?string $department): string
    {
        $department = trim((string) $department);

        return match ($department) {
            'College of Accountancy and Business Education',
            'College of Business and Management' => 'CBA',
            'College of Computer Studies' => 'CCS',
            'College of Criminal Justice Education' => 'CCJE',
            'College of Education' => 'COE',
            'College of Engineering and Architecture',
            'College of Engineering' => 'CEA',
            'College of Maritime Studies' => 'CMS',
            default => $this->departmentInitials($department),
        };
    }

    private function departmentInitials(string $department): string
    {
        if ($department === '') {
            return 'N/A';
        }

        $words = preg_split('/\s+/', preg_replace('/[^A-Za-z0-9 ]/', ' ', $department)) ?: [];
        $skipWords = ['of', 'and', 'the'];
        $initials = '';

        foreach ($words as $word) {
            if ($word === '' || in_array(strtolower($word), $skipWords, true)) {
                continue;
            }

            $initials .= strtoupper($word[0]);

            if (strlen($initials) >= 4) {
                break;
            }
        }

        return $initials !== '' ? $initials : 'N/A';
    }

    // ── Research Management ──────────────────────────────────────────────────

    public function researches(Request $request)
    {
        $query = $this->scopeResearchQuery(Research::with(['user', 'semester']))
            ->whereBetween('year_published', [self::MIN_RESEARCH_YEAR, self::MAX_RESEARCH_YEAR]);
        $selectedSchoolYear = $this->normalizeSchoolYear($request->get('school_year'));
        $selectedSemester = $this->selectedSemester($request->get('semester'));

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($this->currentAdmin()->isGlobalAdmin() && $dept = $request->get('department')) {
            $query->where('department', $dept);
        }
        if ($year = $request->get('year')) {
            $query->where('year_published', $year);
        }
        $this->applySemesterFilter($query, $selectedSchoolYear, $selectedSemester, 'semester');
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author_name', 'like', "%{$search}%");
            });
        }

        $researches  = $query->latest()->paginate(15)->withQueryString();
        $departments = $this->scopeResearchQuery(Research::query())->distinct()->pluck('department')->sort()->values();
        $years = collect(range(self::MAX_RESEARCH_YEAR, self::MIN_RESEARCH_YEAR));
        $semesterOptions = $this->semesterOptions();

        return view('admin.researches', compact('researches', 'departments', 'years', 'selectedSchoolYear', 'selectedSemester') + $semesterOptions);
    }

    public function approveResearch(Research $research)
    {
        abort_if($this->isDepartmentScoped(), 403, 'Only the main admin can approve research submissions.');

        $this->ensureResearchAccess($research);
        $this->ensureResearchIsWritable($research);

        $research->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Research "' . $research->title . '" has been approved.');
    }

    public function archiveResearch(Research $research)
    {
        abort_if($this->isDepartmentScoped(), 403, 'Only the main admin can archive research submissions.');

        $this->ensureResearchAccess($research);
        $this->ensureResearchIsWritable($research);

        abort_unless($research->status === 'approved', 400, 'Only approved research can be archived.');

        $research->update([
            'status' => 'archived',
        ]);

        return back()->with('success', 'Research "' . $research->title . '" has been archived and unpublished.');
    }

    public function publishResearch(Research $research)
    {
        abort_if($this->isDepartmentScoped(), 403, 'Only the main admin can publish archived research submissions.');

        $this->ensureResearchAccess($research);
        $this->ensureResearchIsWritable($research);

        abort_unless($research->status === 'archived', 400, 'Only archived research can be published.');

        $research->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Research "' . $research->title . '" has been published again.');
    }

    public function rejectResearch(Request $request, Research $research)
    {
        abort_if($this->isDepartmentScoped(), 403, 'Only the main admin can reject research submissions.');

        $this->ensureResearchAccess($research);
        $this->ensureResearchIsWritable($research);

        $request->validate(['reason' => 'required|string|max:1000']);

        $research->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        return back()->with('success', 'Research has been rejected.');
    }

    public function deleteResearch(Research $research)
    {
        abort_unless($this->currentAdmin()->isGlobalAdmin(), 403, 'Only main administrators can move research submissions to trash.');

        $this->ensureResearchAccess($research);
        $this->ensureResearchIsWritable($research);

        $research->delete();
        return back()->with('success', 'Research deleted successfully.');
    }

    public function showResearch(Research $research)
    {
        $this->ensureResearchAccess($research);

        $research->load(['user', 'semester']);

        return view('admin.research-detail', compact('research'));
    }

    // ── User Management ──────────────────────────────────────────────────────

    public function users(Request $request)
    {
        $query = User::query();
        $selectedSchoolYear = $this->normalizeSchoolYear($request->get('school_year'));
        $selectedSemester = $this->selectedSemester($request->get('semester'));
        $activeSemester = $this->activeSemester();
        $role = $request->get('role');

        if ($this->isDepartmentScoped()) {
            $query->where('role', '!=', 'admin')
                ->where('department', $this->adminDepartment());
        }

        if ($role) {
            if ($role === 'dean') {
                $query->where('role', 'admin')->where('is_department_dean', true);
            } elseif ($role === 'philcst') {
                $query->whereIn('role', ['user', 'researcher']);
            } elseif ($role === 'student') {
                $this->applyPhilcstMemberTypeFilter($query, 'student');
            } elseif ($role === 'faculty') {
                $this->applyPhilcstMemberTypeFilter($query, 'faculty');
            } else {
                $query->where('role', $role);
            }
        }

        // Filter by member_type inside the combined PhilCST user group.
        if ($memberType = $request->get('member_type')) {
            $this->applyPhilcstMemberTypeFilter($query, $memberType);
        }

        if ($this->currentAdmin()->isGlobalAdmin() && $department = $request->get('department')) {
            $query->where('department', $department);
        }

        if ($request->boolean('imported') && session('imported_user_ids')) {
            $query->whereIn('id', session('imported_user_ids'));
        }

        $hasExplicitSemesterFilter = $request->filled('school_year') || $request->filled('semester');

        if ($hasExplicitSemesterFilter) {
            $this->applySemesterFilter($query, $selectedSchoolYear, $selectedSemester);
        } elseif ($activeSemester && $role !== 'dean') {
            $query->whereHas('semesters', function ($semesterQuery) use ($activeSemester) {
                $semesterQuery
                    ->where('semesters.id', $activeSemester->id)
                    ->where('semester_enrollments.status', SemesterEnrollment::STATUS_ACTIVE);
            });

            $selectedSchoolYear = $activeSemester->school_year;
            $selectedSemester = $activeSemester->semester;
        }

        if ($search = trim((string) $request->get('search'))) {
            $namePrefix = "{$search}%";
            $nameWordPrefix = "% {$search}%";
            $anyMatch = "%{$search}%";
            $isNameSearch = preg_match('/^[\pL\s]+$/u', $search);

            $query->where(function ($q) use ($namePrefix, $nameWordPrefix, $anyMatch, $isNameSearch) {
                $q->where('name', 'like', $namePrefix)
                  ->orWhere('name', 'like', $nameWordPrefix);

                if (! $isNameSearch) {
                    $q->orWhere('email', 'like', $anyMatch)
                      ->orWhere('student_id', 'like', $anyMatch);
                }
            });
        }

        $users = $query
            ->with(['createdBy', 'researcherApprovedBy', 'studentApprovedBy', 'currentSemester'])
            ->withCount('researches')
            ->latest()
            ->paginate(15)
            ->withQueryString();
        $departments = $this->isDepartmentScoped()
            ? [$this->adminDepartment()]
            : $this->departments();
        $semesterOptions = $this->semesterOptions();

        return view('admin.users', [
            'users' => $users,
            'departments' => $departments,
            'fixedDepartment' => $this->adminDepartment(),
            'isDepartmentScoped' => $this->isDepartmentScoped(),
            'selectedSchoolYear' => $selectedSchoolYear,
            'selectedSemester' => $selectedSemester,
            'activeSemester' => $activeSemester,
        ] + $semesterOptions);
    }

    private function applyPhilcstMemberTypeFilter($query, string $memberType): void
    {
        if ($memberType === 'student') {
            $query->where(function ($q) {
                $q->where(function ($studentQuery) {
                    $studentQuery->where('role', 'user')
                        ->whereNotNull('student_id')
                        ->where('student_id', '!=', '');
                })->orWhere(function ($researcherQuery) {
                    $researcherQuery->where('role', 'researcher')
                        ->whereNotNull('graduation_year');
                });
            });
        } elseif ($memberType === 'faculty') {
            $query->where('role', 'researcher')->whereNull('graduation_year');
        }
    }

    public function showUser(User $user)
    {
        $this->ensureUserAccess($user);

        $user->load(['createdBy', 'researcherApprovedBy', 'studentApprovedBy', 'currentSemester']);
        $researches = $user->researches()->with('semester')->latest()->paginate(10);
        return view('admin.user-detail', compact('user', 'researches'));
    }

    public function toggleUserStatus(User $user)
    {
        $this->ensureUserAccess($user);

        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User {$user->name} has been {$status}.");
    }

    public function deleteUser(User $user)
    {
        $this->ensureUserAccess($user);

        $user->delete();
        return back()->with('success', 'User deleted successfully.');
    }

    // ── Researcher Approval ──────────────────────────────────────────────────

    public function pendingResearchers(Request $request)
    {
        $query = User::where('role', 'researcher')->where('is_approved', false);
        $admin = $this->currentAdmin();

        if ($admin->isDepartmentDean() && $admin->department) {
            $query->where('department', $admin->department);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%");
            });
        }

        $pendingResearchers = $query
            ->orderByDesc('researcher_applied_at')
            ->latest()
            ->paginate(15)
            ->withQueryString();
        $approvalDepartment = $admin->isDepartmentDean() ? $admin->department : null;

        return view('admin.pending-researchers', compact('pendingResearchers', 'approvalDepartment'));
    }

    public function pendingStudents(Request $request)
    {
        abort_unless($this->currentAdmin()->isGlobalAdmin(), 403, 'Only main administrators can review pending student accounts.');

        $query = User::query()
            ->where('role', 'user')
            ->where('is_approved', false);

        if ($department = $request->get('department')) {
            $query->where('department', $department);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $pendingStudents = $query->orderBy('department')->latest()->paginate(15)->withQueryString();
        $departmentCounts = User::query()
            ->selectRaw('department, count(*) as total')
            ->where('role', 'user')
            ->where('is_approved', false)
            ->groupBy('department')
            ->orderBy('department')
            ->get();

        return view('admin.pending-students', [
            'pendingStudents'   => $pendingStudents,
            'departmentCounts'  => $departmentCounts,
            'departments'       => $this->departments(),
            'selectedDepartment'=> $department,
        ]);
    }

    public function approveStudent(Request $request, User $user)
    {
        abort_unless($this->currentAdmin()->isGlobalAdmin(), 403, 'Only main administrators can approve student accounts.');
        abort_unless($user->role === 'user' && ! $user->is_approved, 404);

        $data = $request->validate([
            'student_id' => [
                'required',
                'string',
                'max:50',
                'unique:users,student_id',
            ],
        ], [
            'student_id.required' => 'Student ID is required before approval.',
            'student_id.unique'   => 'That Student ID is already assigned to another account.',
        ]);

        $user->update([
            'student_id'  => $data['student_id'],
            'is_approved' => true,
            'student_approved_by' => $this->currentAdmin()->id,
            'student_approved_at' => now(),
        ]);

        try {
            Mail::to($user->email)->send(new \App\Mail\StudentApproved($user));
        } catch (\Exception $e) {
            // Mail failed silently
        }

        return back()->with('success', $user->name . ' has been approved and assigned Student ID ' . $data['student_id'] . '.');
    }

    public function rejectStudent(User $user)
    {
        abort_unless($this->currentAdmin()->isGlobalAdmin(), 403, 'Only main administrators can reject student accounts.');
        abort_unless($user->role === 'user' && ! $user->is_approved, 404);

        $user->delete();

        return back()->with('success', 'Student account has been rejected and deleted.');
    }

    public function approveResearcher(User $user, Request $request)
    {
        if (! $this->currentAdmin()->canApproveResearcher($user)) {
            abort(403, 'You can only approve researchers from your assigned department.');
        }

        $data = $request->validate([
            'researcher_end_date' => ['required', 'date', 'after:today'],
        ], [
            'researcher_end_date.required' => 'Researcher End Date is required.',
            'researcher_end_date.after' => 'Researcher End Date must be a future date.',
        ]);

        $user->update([
            'is_approved' => true,
            'researcher_approved_by' => $this->currentAdmin()->id,
            'researcher_approved_at' => now(),
            'researcher_end_date' => $data['researcher_end_date'],
        ]);

        try {
            Mail::to($user->email)->send(new \App\Mail\ResearcherApproved($user));
        } catch (\Exception $e) {
            // Mail failed silently
        }

        return back()->with('success', $user->name . ' has been approved as a Researcher.');
    }

    public function rejectResearcher(Request $request, User $user)
    {
        if (! $this->currentAdmin()->canApproveResearcher($user)) {
            abort(403, 'You can only reject researchers from your assigned department.');
        }

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'rejection_reason.required' => 'Please tell the applicant what they need to fix before rejecting.',
            'rejection_reason.min' => 'The rejection instruction must be at least 10 characters.',
            'rejection_reason.max' => 'The rejection instruction must not exceed 2000 characters.',
        ]);

        foreach (array_merge($user->verification_documents ?? [], $user->research_documents ?? []) as $document) {
            if (! empty($document['path'])) {
                Storage::disk('public')->delete($document['path']);
            }
        }

        $user->update([
            'role' => 'user',
            'is_approved' => true,
            'year_level' => null,
            'course_duration' => null,
            'graduation_year' => null,
            'researcher_end_date' => null,
            'researcher_rejection_reason' => $data['rejection_reason'],
            'researcher_rejected_at' => now(),
            'researcher_approved_by' => null,
            'researcher_approved_at' => null,
            'verification_documents' => null,
            'research_documents' => null,
        ]);

        return back()->with('success', $user->name . '\'s researcher application was rejected. The account remains active as a Student.');
    }

    // ── Messages ─────────────────────────────────────────────────────────────

    public function messages(Request $request)
    {
        abort_if($this->isDepartmentScoped(), 403, 'Department deans cannot access central message management.');

        $filter = $request->get('filter', 'all');
        $query  = Message::latest();

        if ($filter === 'unread') {
            $query->where('is_read', false);
        }

        $messages    = $query->paginate(15)->withQueryString();
        $unreadCount = Message::where('is_read', false)->count();

        return view('admin.messages', compact('messages', 'unreadCount', 'filter'));
    }

    public function showMessage(Message $message)
    {
        abort_if($this->isDepartmentScoped(), 403, 'Department deans cannot access central message management.');

        $message->update(['is_read' => true]);
        return view('admin.message-detail', compact('message'));
    }

    public function deleteMessage(Message $message)
    {
        abort_if($this->isDepartmentScoped(), 403, 'Department deans cannot access central message management.');

        $message->delete();
        return redirect()->route('admin.messages')->with('success', 'Message deleted.');
    }

    public function markAllRead()
    {
        abort_if($this->isDepartmentScoped(), 403, 'Department deans cannot access central message management.');

        Message::where('is_read', false)->update(['is_read' => true]);
        return back()->with('success', 'All messages marked as read.');
    }

    public function departmentKeys(Request $request)
    {
        abort_unless(
            $this->currentAdmin()->canManageDepartmentKeys() || $this->currentAdmin()->isDepartmentDean(),
            403,
            'Only admins and assigned deans can access department keys.'
        );

        $this->autoArchiveDepartmentKeys();

        $selectedDepartment = $this->isDepartmentScoped()
            ? $this->adminDepartment()
            : $request->get('department');
        $selectedStatus = in_array($request->get('status'), ['active', 'inactive', 'expired', 'archived'], true)
            ? $request->get('status')
            : null;
        $selectedSchoolYear = $request->get('school_year');
        $selectedSemester = in_array($request->get('semester'), ['1st Sem', '2nd Sem'], true)
            ? $request->get('semester')
            : null;

        $optionQuery = DepartmentAccessKey::query()
            ->when($this->isDepartmentScoped(), fn ($query) => $query->where('department', $this->adminDepartment()));

        $schoolYears = (clone $optionQuery)
            ->whereNotNull('school_year')
            ->distinct()
            ->orderByDesc('school_year')
            ->pluck('school_year');

        $recentInactiveCutoff = now()->subDays(30);

        $currentKeysQuery = DepartmentAccessKey::query()
            ->whereNull('archived_at')
            ->where('expires_at', '>', now())
            ->where(function ($query) use ($recentInactiveCutoff) {
                $query->where('is_active', true)
                    ->orWhere('updated_at', '>=', $recentInactiveCutoff);
            });

        $this->applyDepartmentKeyFilters(
            $currentKeysQuery,
            $selectedDepartment,
            $selectedStatus,
            $selectedSchoolYear,
            $selectedSemester
        );

        $historyKeysQuery = DepartmentAccessKey::query()
            ->where(function ($query) use ($recentInactiveCutoff) {
                $query->whereNotNull('archived_at')
                    ->orWhere('expires_at', '<=', now())
                    ->orWhere(function ($inactiveQuery) use ($recentInactiveCutoff) {
                        $inactiveQuery->where('is_active', false)
                            ->where('updated_at', '<', $recentInactiveCutoff);
                    });
            });

        $this->applyDepartmentKeyFilters(
            $historyKeysQuery,
            $selectedDepartment,
            $selectedStatus,
            $selectedSchoolYear,
            $selectedSemester
        );

        $currentKeys = $currentKeysQuery
            ->orderBy('department')
            ->orderByDesc('expires_at')
            ->paginate(10, ['*'], 'current_page')
            ->withQueryString();

        $historyKeys = $historyKeysQuery
            ->orderByDesc('archived_at')
            ->orderByDesc('expires_at')
            ->paginate(10, ['*'], 'history_page')
            ->withQueryString();

        return view('admin.department-keys', [
            'currentKeys'         => $currentKeys,
            'historyKeys'         => $historyKeys,
            'departments'         => $this->departments(),
            'schoolYears'         => $schoolYears,
            'canManageKeys'       => $this->currentAdmin()->canManageDepartmentKeys(),
            'adminDepartment'     => $this->adminDepartment(),
            'selectedDepartment'  => $selectedDepartment,
            'selectedStatus'      => $selectedStatus,
            'selectedSchoolYear'  => $selectedSchoolYear,
            'selectedSemester'    => $selectedSemester,
        ]);
    }

    private function applyDepartmentKeyFilters($query, ?string $department, ?string $status, ?string $schoolYear, ?string $semester): void
    {
        $query
            ->when($this->isDepartmentScoped(), fn ($inner) => $inner->where('department', $this->adminDepartment()))
            ->when(! $this->isDepartmentScoped() && filled($department), fn ($inner) => $inner->where('department', $department))
            ->when(filled($schoolYear), fn ($inner) => $inner->where('school_year', $schoolYear))
            ->when(filled($semester), fn ($inner) => $inner->where('semester', $semester));

        if (! $status) {
            return;
        }

        match ($status) {
            'active' => $query->whereNull('archived_at')
                ->where('is_active', true)
                ->where('expires_at', '>', now()),
            'inactive' => $query->whereNull('archived_at')
                ->where('is_active', false)
                ->where('expires_at', '>', now()),
            'expired' => $query->where('expires_at', '<=', now()),
            'archived' => $query->whereNotNull('archived_at')
                ->where('expires_at', '>', now()),
            default => null,
        };
    }

    private function autoArchiveDepartmentKeys(): void
    {
        $now = now();
        $inactiveCutoff = $now->copy()->subDays(30);

        DepartmentAccessKey::query()
            ->where('is_active', true)
            ->where('expires_at', '<=', $now)
            ->update(['is_active' => false]);

        DepartmentAccessKey::query()
            ->whereNull('archived_at')
            ->where(function ($query) use ($now, $inactiveCutoff) {
                $query->where('expires_at', '<=', $now->copy()->subDays(30))
                    ->orWhere(function ($inactiveQuery) use ($inactiveCutoff) {
                        $inactiveQuery->where('is_active', false)
                            ->where('updated_at', '<', $inactiveCutoff);
                    });
            })
            ->update([
                'is_active' => false,
                'archived_at' => $now,
            ]);

        DepartmentAccessKey::query()
            ->whereNull('archived_at')
            ->orderBy('id')
            ->chunkById(100, function ($keys) use ($now) {
                foreach ($keys as $key) {
                    if ($this->isPastSchoolYear($key->school_year)) {
                        $key->update([
                            'is_active' => false,
                            'archived_at' => $now,
                        ]);
                    }
                }
            });
    }

    private function isPastSchoolYear(?string $schoolYear): bool
    {
        if (! $schoolYear || ! preg_match('/^\d{4}-(\d{4})$/', $schoolYear, $matches)) {
            return false;
        }

        return (int) $matches[1] < (int) now()->year;
    }

    public function storeDepartmentKey(Request $request)
    {
        abort_unless($this->currentAdmin()->canManageDepartmentKeys(), 403, 'Only main administrators can manage department keys.');

        $data = $request->validate([
            'department'  => 'required|string|max:255',
            'semester'    => 'required|in:1st Sem,2nd Sem',
            'school_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'access_key'  => 'required|string|min:6|max:255',
            'expires_at'  => 'required|date|after:today',
        ]);

        DepartmentAccessKey::where('department', $data['department'])
            ->where('is_active', true)
            ->update(['is_active' => false]);

        DepartmentAccessKey::create([
            'department'  => $data['department'],
            'semester'    => $data['semester'],
            'school_year' => $data['school_year'],
            'access_key'  => Hash::make($data['access_key']),
            'access_key_plain' => $data['access_key'],
            'expires_at'  => $data['expires_at'],
            'archived_at' => null,
            'is_active'   => true,
        ]);

        $departmentDeans = User::query()
            ->where('role', 'admin')
            ->where('is_department_dean', true)
            ->where('department', $data['department'])
            ->get();

        foreach ($departmentDeans as $dean) {
            try {
                Mail::to($dean->email)->send(new \App\Mail\DepartmentKeyAssigned(
                    $dean,
                    $data['department'],
                    $data['semester'],
                    $data['school_year'],
                    $data['access_key'],
                    \Illuminate\Support\Carbon::parse($data['expires_at'])->format('F j, Y')
                ));
            } catch (\Exception $e) {
                // Mail failed silently
            }
        }

        $message = 'Department access key created successfully.';

        if ($departmentDeans->isNotEmpty()) {
            $message .= ' It was also sent to the assigned dean email.';
        } else {
            $message .= ' No department dean email was found for this department.';
        }

        return back()->with('success', $message);
    }

    public function toggleDepartmentKey(DepartmentAccessKey $departmentKey)
    {
        abort_unless($this->currentAdmin()->canManageDepartmentKeys(), 403, 'Only main administrators can manage department keys.');

        if (! $departmentKey->is_active) {
            DepartmentAccessKey::where('department', $departmentKey->department)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $departmentKey->update([
            'is_active' => ! $departmentKey->is_active,
            'archived_at' => ! $departmentKey->is_active ? null : $departmentKey->archived_at,
        ]);

        return back()->with('success', 'Department key status updated.');
    }

    public function deleteDepartmentKey(DepartmentAccessKey $departmentKey)
    {
        abort_unless($this->currentAdmin()->canManageDepartmentKeys(), 403, 'Only main administrators can manage department keys.');

        $department = $departmentKey->department;
        $semester = $departmentKey->semester;
        $schoolYear = $departmentKey->school_year;

        $departmentKey->update([
            'is_active' => false,
            'archived_at' => now(),
        ]);

        return back()->with('success', "Department key for {$department} ({$semester}, {$schoolYear}) archived.");
    }

    // ── Add Research (Admin) ─────────────────────────────────────────────────

    public function semesters(Request $request)
    {
        $selectedSchoolYear = $this->normalizeSchoolYear($request->get('school_year'));
        $selectedSemester = $this->selectedSemester($request->get('semester'));
        $selectedStatus = in_array($request->get('status'), ['active', 'closed', 'archived'], true)
            ? $request->get('status')
            : null;

        $this->closeExpiredSemesters();

        $query = Semester::query()
            ->withCount([
                'users' => function ($userQuery) {
                    if ($this->isDepartmentScoped()) {
                        $userQuery->where('department', $this->adminDepartment());
                    }
                },
                'researches' => function ($researchQuery) {
                    $this->scopeResearchQuery($researchQuery);
                },
                'researches as total_researches_count' => function ($researchQuery) {
                    $researchQuery->withTrashed();
                },
            ])
            ->when($selectedSchoolYear, fn ($inner) => $inner->where('school_year', $selectedSchoolYear))
            ->when($selectedSemester, fn ($inner) => $inner->where('semester', $selectedSemester));

        if ($selectedStatus === 'active') {
            $query->active();
        } elseif (in_array($selectedStatus, ['closed', 'archived'], true)) {
            $query->closed();
        }

        $semesters = $query
            ->orderByDesc('school_year')
            ->orderBy('semester')
            ->paginate(12)
            ->withQueryString();

        return view('admin.semesters', [
            'semesters' => $semesters,
            'selectedSchoolYear' => $selectedSchoolYear,
            'selectedSemester' => $selectedSemester,
            'selectedStatus' => $selectedStatus === 'archived' ? 'closed' : $selectedStatus,
            'canArchiveSemesters' => $this->currentAdmin()->isGlobalAdmin(),
            'canManageSemesters' => $this->canManageSemesters(),
        ] + $this->semesterOptions());
    }

    public function storeSemester(Request $request)
    {
        abort_unless($this->currentAdmin()->isGlobalAdmin(), 403, 'Only main administrators can create semesters.');

        $validator = validator($request->all(), [
            'school_year' => ['required', 'string', 'max:20'],
            'semester' => ['required', Rule::in(self::SEMESTER_OPTIONS)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request) {
            if (! $request->boolean('is_active') || ! $request->filled('end_date')) {
                return;
            }

            if (Carbon::parse($request->input('end_date'))->lt(now()->startOfDay())) {
                $validator->errors()->add('end_date', 'An active semester must have a valid end date today or later.');
            }
        });

        $data = $validator->validate();

        $schoolYear = $this->normalizeSchoolYear($data['school_year']);

        if (! $schoolYear) {
            return back()
                ->withErrors(['school_year' => 'Use a valid school year like 2025-2026.'])
                ->withInput();
        }

        $semester = Semester::updateOrCreate(
            [
                'school_year' => $schoolYear,
                'semester' => $data['semester'],
            ],
            [
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_active' => $request->boolean('is_active'),
                'created_by' => $this->currentAdmin()->id,
                'closed_by' => null,
                'closed_at' => null,
            ]
        );

        return back()->with('success', $semester->label . ' saved.');
    }

    public function updateSemester(Request $request, Semester $semester)
    {
        abort_unless($this->canManageSemesters(), 403, 'Only administrators and department deans can correct semesters.');

        $validator = validator($request->all(), [
            'school_year' => ['required', 'string', 'max:20'],
            'semester' => ['required', Rule::in(self::SEMESTER_OPTIONS)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request, $semester) {
            $schoolYear = $this->normalizeSchoolYear($request->input('school_year'));

            if (! $schoolYear) {
                $validator->errors()->add('school_year', 'Use a valid school year like 2025-2026.');

                return;
            }

            $semesterValue = $request->input('semester');

            if (! in_array($semesterValue, self::SEMESTER_OPTIONS, true)) {
                return;
            }

            $duplicateExists = Semester::query()
                ->where('school_year', $schoolYear)
                ->where('semester', $semesterValue)
                ->where('id', '!=', $semester->id)
                ->exists();

            if ($duplicateExists) {
                $validator->errors()->add('school_year', 'A semester record already exists for that school year and semester.');
            }

            if (! $request->boolean('is_active') || ! $request->filled('end_date')) {
                return;
            }

            try {
                $endDate = Carbon::parse($request->input('end_date'));
            } catch (\Throwable) {
                return;
            }

            if ($endDate->lt(now()->startOfDay())) {
                $validator->errors()->add('end_date', 'An active semester must have a valid end date today or later.');
            }
        });

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'semesterUpdate')
                ->withInput()
                ->with('semester_update_id', $semester->id);
        }

        $data = $validator->validated();
        $schoolYear = $this->normalizeSchoolYear($data['school_year']);
        $isActive = $request->boolean('is_active');
        $wasActive = (bool) $semester->is_active;

        DB::transaction(function () use ($semester, $schoolYear, $data, $isActive, $wasActive) {
            $semester->fill([
                'school_year' => $schoolYear,
                'semester' => $data['semester'],
                'start_date' => Carbon::parse($data['start_date'])->toDateString(),
                'end_date' => Carbon::parse($data['end_date'])->toDateString(),
                'is_active' => $isActive,
            ]);

            if ($isActive) {
                $semester->closed_by = null;
                $semester->closed_at = null;
            } elseif ($wasActive || ! $semester->closed_at) {
                $semester->closed_by = $this->currentAdmin()->id;
                $semester->closed_at = now();
            }

            $semester->save();

            if ($isActive) {
                SemesterEnrollment::query()
                    ->where('semester_id', $semester->id)
                    ->where('status', SemesterEnrollment::STATUS_ARCHIVED)
                    ->update([
                        'status' => SemesterEnrollment::STATUS_ACTIVE,
                        'updated_at' => now(),
                    ]);

                return;
            }

            SemesterEnrollment::query()
                ->where('semester_id', $semester->id)
                ->where('status', SemesterEnrollment::STATUS_ACTIVE)
                ->update([
                    'status' => SemesterEnrollment::STATUS_ARCHIVED,
                    'updated_at' => now(),
                ]);
        });

        return back()->with('success', $semester->fresh()->label . ' updated.');
    }

    public function showSemester(Semester $semester)
    {
        $semester->load(['creator', 'closedBy']);
        $totalResearchRecords = $semester->researches()->withTrashed()->count();

        $usersQuery = $semester->users()
            ->with('currentSemester')
            ->withCount('researches')
            ->orderBy('name');

        if ($this->isDepartmentScoped()) {
            $usersQuery->where('department', $this->adminDepartment());
        }

        $researchesQuery = $semester->researches()
            ->with('user')
            ->latest();

        $this->scopeResearchQuery($researchesQuery);

        $users = $usersQuery
            ->paginate(10, ['*'], 'users_page')
            ->withQueryString();
        $researches = $researchesQuery
            ->paginate(10, ['*'], 'researches_page')
            ->withQueryString();

        return view('admin.semester-detail', [
            'semester' => $semester,
            'users' => $users,
            'researches' => $researches,
            'canArchiveSemesters' => $this->currentAdmin()->isGlobalAdmin(),
            'canManageSemesters' => $this->canManageSemesters(),
            'semesterOptions' => self::SEMESTER_OPTIONS,
            'totalResearchRecords' => $totalResearchRecords,
        ]);
    }

    public function archiveSemester(Semester $semester)
    {
        abort_unless($this->currentAdmin()->isGlobalAdmin(), 403, 'Only main administrators can close semesters.');

        if ($semester->isArchived()) {
            return back()->with('success', $semester->label . ' is already closed.');
        }

        $semester->update([
            'is_active' => false,
            'closed_at' => now(),
            'closed_by' => $this->currentAdmin()->id,
        ]);

        SemesterEnrollment::query()
            ->where('semester_id', $semester->id)
            ->where('status', SemesterEnrollment::STATUS_ACTIVE)
            ->update([
                'status' => SemesterEnrollment::STATUS_ARCHIVED,
                'updated_at' => now(),
            ]);

        return back()->with('success', $semester->label . ' closed. Linked users and research records remain available for reference.');
    }

    public function destroySemester(Semester $semester)
    {
        abort_unless($this->canManageSemesters(), 403, 'Only administrators and department deans can delete semesters.');

        if ($this->semesterHasResearchRecords($semester)) {
            return back()->with('error', $semester->label . ' has linked research records. Correct the semester information instead of deleting it.');
        }

        $label = $semester->label;

        DB::transaction(function () use ($semester) {
            User::query()
                ->where('current_semester_id', $semester->id)
                ->update([
                    'current_semester_id' => null,
                    'updated_at' => now(),
                ]);

            SemesterEnrollment::query()
                ->where('semester_id', $semester->id)
                ->delete();

            $semester->delete();
        });

        return redirect()
            ->route('admin.semesters')
            ->with('success', $label . ' deleted.');
    }

    public function addResearch()
    {
        abort_if($this->isDepartmentScoped(), 403, 'Department deans cannot add research records directly.');

        return view('admin.add-research');
    }

    public function storeResearch(Request $request)
    {
        abort_if($this->isDepartmentScoped(), 403, 'Department deans cannot add research records directly.');

        $programsByDepartment = Research::programsByDepartment();
        $submissionCategories = Research::adminSubmissionCategories();

        $validator = validator($request->all(), [
            'title'          => ['required', 'string', 'max:500'],
            'submission_category' => ['required', Rule::in(array_keys($submissionCategories))],
            'type'           => ['required', Rule::in(Research::journalTypeOptions())],
            'authors'        => ['required', 'array', 'min:1', 'max:12'],
            'authors.*'      => ['required', 'string', 'max:150'],
            'department'     => ['required', Rule::in(Research::departmentOptions())],
            'course'         => ['nullable', 'string', 'max:255'],
            'year_published' => ['required', 'integer', 'min:' . self::MIN_RESEARCH_YEAR, 'max:' . self::MAX_RESEARCH_YEAR],
            'keywords'       => ['nullable', 'string', 'max:500'],
            'abstract'       => ['required', 'string'],
            'file'           => ['required', 'file', 'mimes:pdf', 'max:30720'],
        ], [
            'authors.required' => 'Add at least one author or researcher.',
            'authors.*.required' => 'Each author or researcher name is required.',
            'course.required' => 'Program is required for Student Research Journal.',
            'file.mimes' => 'The full paper must be a PDF file.',
            'file.max' => 'The full paper PDF must not exceed 30MB.',
        ]);

        $validator->after(function ($validator) use ($request, $programsByDepartment) {
            if ($request->input('submission_category') !== Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL) {
                return;
            }

            $department = (string) $request->input('department');
            $program = (string) $request->input('course');
            $allowedPrograms = $programsByDepartment[$department] ?? [];

            if ($program === '') {
                $validator->errors()->add('course', 'Program is required for Student Research Journal.');
                return;
            }

            if (! in_array($program, $allowedPrograms, true)) {
                $validator->errors()->add('course', 'Choose a valid program for the selected department.');
            }
        });

        $data = $validator->validate();

        $authors = collect($data['authors'])
            ->map(fn ($author) => trim((string) $author))
            ->filter()
            ->values();

        $isStudentJournal = $data['submission_category'] === Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL;
        $program = $isStudentJournal ? ($data['course'] ?? null) : null;

        $filePath = null;
        $fileName = null;

        $file     = $request->file('file');
        $storedFileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('researches', $storedFileName, 'public');
        $fileName = $file->getClientOriginalName();

        Research::create([
            'title'          => $data['title'],
            'submission_category' => $data['submission_category'],
            'issn'           => Research::issnForSubmissionCategory($data['submission_category']),
            'type'           => $data['type'],
            'author_name'    => $authors->implode('; '),
            'authors'        => $authors->all(),
            'user_id'        => Auth::id(),
            'department'     => $data['department'],
            'course'         => $program,
            'program'        => $program,
            'year_published' => $data['year_published'],
            'keywords'       => $data['keywords'] ?? null,
            'abstract'       => $data['abstract'],
            'file_path'      => $filePath,
            'file_name'      => $fileName,
            'status'         => 'approved',
            'approved_by'    => Auth::id(),
            'approved_at'    => now(),
        ]);

        $categoryLabel = $submissionCategories[$data['submission_category']] ?? 'Research Journal';

        return redirect()->route('admin.researches')->with('success', $categoryLabel . ' added and approved successfully!');
    }

    // ── Admin Management ──────────────────────────────────────────────────────

    public function createAdmin()
    {
        abort_unless($this->currentAdmin()->canManageDepartmentKeys(), 403, 'Only main administrators can create dean and admin accounts.');

        return view('admin.create-admin', [
            'departments' => $this->departments(),
        ]);
    }

    public function createUser()
    {
        abort(403, 'Manual user creation has been disabled.');
    }

    public function storeAdmin(Request $request)
    {
        abort_unless($this->currentAdmin()->canManageDepartmentKeys(), 403, 'Only main administrators can create dean accounts.');

        $data = $request->validate([
            'firstname'  => ['required', 'regex:/^[a-zA-Z\s]+$/', 'max:255'],
            'lastname'   => ['required', 'regex:/^[a-zA-Z\s]+$/', 'max:255'],
            'middlename' => ['nullable', 'regex:/^[a-zA-Z\s]+$/', 'max:255'],
            'dean_id'    => ['required', 'regex:/^[A-Za-z0-9\-]+$/', 'max:50', 'unique:users,student_id'],
            'department' => 'required|string|max:255',
        ], [
            'firstname.regex'  => 'First name must contain letters only.',
            'lastname.regex'   => 'Last name must contain letters only.',
            'middlename.regex' => 'Middle name must contain letters only.',
            'dean_id.regex'    => 'Dean ID may only contain letters, numbers, and hyphens.',
        ]);

        $fullName = trim(implode(' ', array_filter([
            $data['firstname'],
            $data['middlename'] ?? null,
            $data['lastname'],
        ])));

        $emailPrefix = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '.', trim($data['dean_id'])));
        $emailPrefix = trim($emailPrefix, '.') ?: 'dean';
        $generatedEmail = $emailPrefix . '@ube.local';
        $emailCounter = 1;

        while (User::where('email', $generatedEmail)->exists()) {
            $generatedEmail = $emailPrefix . '.' . $emailCounter . '@ube.local';
            $emailCounter++;
        }

        $generatedPassword = $data['dean_id'] . '_Dean@1';

        User::create([
            'name'               => $fullName,
            'email'              => $generatedEmail,
            'password'           => Hash::make($generatedPassword),
            'student_id'         => $data['dean_id'],
            'created_by'         => $this->currentAdmin()->id,
            'role'               => 'admin',
            'department'         => $data['department'],
            'is_department_dean' => true,
            'is_active'          => true,
            'is_approved'        => true,
            'last_seen_at'       => null,
        ]);

        return redirect()->route('admin.users')->with(
            'success',
            'Dean account created successfully. Login ID: ' . $data['dean_id'] . ' | Default password: ' . $generatedPassword
        );
    }

    // ── Reports ───────────────────────────────────────────────────────────────

    public function storeUser(Request $request)
    {
        abort(403, 'Manual user creation has been disabled.');
    }

    public function importUsers(Request $request)
    {
        abort_unless($this->currentAdmin()->canImportUsers(), 403, 'Only department deans can import user accounts.');

        $data = $request->validateWithBag('importUsers', [
            'semester' => ['required', Rule::in(self::SEMESTER_OPTIONS)],
            'school_year' => ['required', 'string', 'max:20'],
            'end_date' => ['required', 'date', 'after_or_equal:today'],
            'file' => ['required', 'file', 'extensions:xlsx,csv,txt', 'max:5120'],
        ]);

        $schoolYear = $this->normalizeSchoolYear($data['school_year']);

        if (! $schoolYear) {
            return redirect()->route('admin.users')
                ->withErrors(['school_year' => 'Use a valid school year like 2025-2026.'], 'importUsers')
                ->withInput();
        }

        $semester = $this->semesterForImport($schoolYear, $data['semester'], $data['end_date']);

        try {
            $rows = $this->readUserImportRows($data['file']->getRealPath(), strtolower($data['file']->getClientOriginalExtension()));
        } catch (\Throwable $exception) {
            return redirect()->route('admin.users')->with('import_errors', [
                ['row' => '-', 'error' => $exception->getMessage()],
            ]);
        }

        $errors = [];
        $validUsers = [];
        $importPreview = [];
        $skippedUsers = [];
        $existingAssignments = [];
        $seenEmails = [];
        $seenIds = [];
        $existingEmails = [];
        $existingIds = [];

        User::query()
            ->select('id', 'name', 'email', 'student_id', 'is_approved')
            ->get()
            ->each(function (User $user) use (&$existingEmails, &$existingIds) {
                $userSummary = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'student_id' => $user->student_id,
                    'is_approved' => (bool) $user->is_approved,
                ];

                if ($user->email) {
                    $existingEmails[strtolower($user->email)] = $userSummary;
                }

                if ($user->student_id) {
                    $existingIds[strtolower($user->student_id)] = $userSummary;
                }
            });

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $normalized = $this->normalizeImportedUserRow($row);
            $rowErrors = $this->validateImportedUserRow($normalized);

            if ($this->isDepartmentScoped()) {
                $normalized['department'] = $this->adminDepartment();
            }

            if (! $this->isDepartmentScoped() && $normalized['department'] && ! in_array($normalized['department'], $this->departments(), true)) {
                $rowErrors[] = 'Invalid department.';
            }

            $identifier = $normalized['member_type'] === 'faculty'
                ? $normalized['employee_id']
                : $normalized['student_id'];
            $role = $normalized['role'] ?: ($normalized['member_type'] === 'faculty' ? 'researcher' : 'user');

            if ($normalized['member_type'] === 'student' && $identifier && ! preg_match('/^[0-9]{8}$/', $identifier)) {
                $rowErrors[] = 'Student ID must be exactly 8 numbers.';
            }

            if ($normalized['member_type'] === 'faculty' && $identifier && ! preg_match('/^[A-Za-z0-9\-]+$/', $identifier)) {
                $rowErrors[] = 'Employee ID may only contain letters, numbers, and hyphens.';
            }

            $idKey = strtolower((string) $identifier);

            $email = $normalized['email'] ?: $this->generateImportedUserEmail($identifier);
            $emailKey = strtolower($email);
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = 'Invalid email.';
            }

            if ($rowErrors) {
                $errors[] = ['row' => $rowNumber, 'error' => implode(' ', array_unique($rowErrors))];
                continue;
            }

            if ($idKey !== '' && isset($seenIds[$idKey])) {
                $skippedUsers[] = [
                    'row' => $rowNumber,
                    'name' => $this->formatImportedUserName($normalized),
                    'login_id' => $identifier,
                    'email' => $email,
                    'reason' => 'Duplicate ID inside the uploaded file. First occurrence will be imported.',
                ];
                continue;
            }

            if (isset($seenEmails[$emailKey])) {
                $skippedUsers[] = [
                    'row' => $rowNumber,
                    'name' => $this->formatImportedUserName($normalized),
                    'login_id' => $identifier,
                    'email' => $email,
                    'reason' => 'Duplicate email inside the uploaded file. First occurrence will be imported.',
                ];
                continue;
            }

            $seenIds[$idKey] = true;
            $seenEmails[$emailKey] = true;

            $existingUser = $existingEmails[$emailKey] ?? ($idKey !== '' ? ($existingIds[$idKey] ?? null) : null);
            if ($existingUser) {
                $existingAssignments[$existingUser['id']] = [
                    'id' => $existingUser['id'],
                    'name' => $existingUser['name'],
                    'login_id' => $existingUser['student_id'] ?: $identifier,
                    'email' => $existingUser['email'],
                ];
                $importPreview[] = [
                    'name' => $existingUser['name'],
                    'login_id' => $existingUser['student_id'] ?: $identifier,
                    'email' => $existingUser['email'],
                    'action' => 'Updated',
                ];
                continue;
            }

            $yearLevel = $normalized['year_level'] ? (int) $normalized['year_level'] : null;
            $courseDuration = $role === 'researcher' && $normalized['member_type'] === 'student' && $yearLevel ? 4 : null;
            $graduationYear = $courseDuration ? ((int) date('Y') + ($courseDuration - $yearLevel)) : null;
            $password = $normalized['password'] ?: $this->generateImportedUserPassword($identifier, $normalized['firstname']);
            $now = now();

            $payload = [
                'name' => $this->formatImportedUserName($normalized),
                'email' => $email,
                'password' => Hash::make($password),
                'role' => $role,
                'department' => $normalized['department'],
                'current_semester_id' => $semester->id,
                'student_id' => $identifier,
                'created_by' => $this->currentAdmin()->id,
                'year_level' => $yearLevel,
                'course_duration' => $courseDuration,
                'graduation_year' => $graduationYear,
                'is_approved' => true,
                'is_active' => true,
                'student_approved_by' => null,
                'student_approved_at' => null,
                'researcher_approved_by' => null,
                'researcher_approved_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($role === 'user') {
                $payload['student_approved_by'] = $this->currentAdmin()->id;
                $payload['student_approved_at'] = $now;
            }

            if ($role === 'researcher') {
                $payload['researcher_approved_by'] = $this->currentAdmin()->id;
                $payload['researcher_approved_at'] = $now;
            }

            if (Schema::hasColumn('users', 'middle_name')) {
                $payload['middle_name'] = $normalized['middlename'];
            }

            $validUsers[] = $payload;
            $importPreview[] = [
                'name' => $payload['name'],
                'login_id' => $identifier,
                'email' => $email,
                'action' => 'Created',
            ];
        }

        if (empty($rows)) {
            return redirect()->route('admin.users')
                ->with('error', 'No rows were found in the uploaded file. Check that the first sheet has headers and user rows.');
        }

        $importedUserIds = [];
        $createdUserIds = [];
        $updatedUserIds = array_keys($existingAssignments);

        if ($validUsers || $existingAssignments) {
            try {
                DB::transaction(function () use ($validUsers, $updatedUserIds, $semester, &$createdUserIds, &$importedUserIds) {
                    if ($validUsers) {
                        $validEmails = array_column($validUsers, 'email');
                        User::insert($validUsers);
                        $createdUserIds = User::query()
                            ->whereIn('email', $validEmails)
                            ->pluck('id')
                            ->all();
                    }

                    if ($updatedUserIds) {
                        User::query()
                            ->whereIn('id', $updatedUserIds)
                            ->update([
                                'current_semester_id' => $semester->id,
                                'updated_at' => now(),
                            ]);
                    }

                    $importedUserIds = array_values(array_unique(array_merge($createdUserIds, $updatedUserIds)));

                    if ($importedUserIds) {
                        $timestamp = now();
                        $assignmentRows = array_map(fn ($userId) => [
                            'user_id' => $userId,
                            'semester_id' => $semester->id,
                            'status' => SemesterEnrollment::STATUS_ACTIVE,
                            'enrolled_by' => $this->currentAdmin()->id,
                            'enrolled_at' => $timestamp,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ], $importedUserIds);

                        DB::table('semester_enrollments')->upsert(
                            $assignmentRows,
                            ['user_id', 'semester_id'],
                            ['status', 'enrolled_by', 'enrolled_at', 'updated_at']
                        );
                    }
                });
            } catch (\Throwable $exception) {
                return redirect()->route('admin.users')->with('import_errors', [
                    ['row' => '-', 'error' => 'Import failed while saving users: ' . $exception->getMessage()],
                ]);
            }
        }

        $redirect = count($importedUserIds) > 0
            ? redirect()->route('admin.users', ['imported' => 1])
            : redirect()->route('admin.users');

        $messageType = (count($validUsers) + count($existingAssignments)) > 0 ? 'success' : (count($skippedUsers) > 0 && count($errors) === 0 ? 'success' : 'error');
        $message = count($validUsers) . ' new user(s) imported, '
            . count($existingAssignments) . ' existing user(s) assigned to ' . $semester->label . '. '
            . count($skippedUsers) . ' duplicate row(s) skipped. '
            . count($errors) . ' row(s) failed.';

        return $redirect
            ->with($messageType, $message)
            ->with('import_errors', $errors)
            ->with('import_skipped', $skippedUsers)
            ->with('import_preview', $importPreview)
            ->with('import_semester', $semester->label)
            ->with('imported_user_ids', $importedUserIds);
    }

    private function semesterForImport(string $schoolYear, string $semesterValue, string $endDate): Semester
    {
        $this->closeExpiredSemesters();

        $endDate = Carbon::parse($endDate)->toDateString();

        $semester = Semester::query()
            ->where('school_year', $schoolYear)
            ->where('semester', $semesterValue)
            ->first();

        if (! $semester) {
            return Semester::create([
                'school_year' => $schoolYear,
                'semester' => $semesterValue,
                'end_date' => $endDate,
                'is_active' => true,
                'created_by' => $this->currentAdmin()->id,
            ]);
        }

        if (! $semester->isOpen() || ! $semester->end_date) {
            $semester->update([
                'end_date' => $endDate,
                'is_active' => true,
                'closed_by' => null,
                'closed_at' => null,
            ]);
        }

        return $semester->fresh();
    }

    private function readUserImportRows(string $path, string $extension): array
    {
        return $extension === 'xlsx'
            ? $this->readXlsxRows($path)
            : $this->readCsvRows($path);
    }

    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');

        if (! $handle) {
            throw new \RuntimeException('Unable to read uploaded CSV file.');
        }

        $headers = null;
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = array_map(fn ($header) => $this->normalizeImportHeader((string) $header), $line);
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $row[$header] = trim((string) ($line[$index] ?? ''));
                }
            }

            if (array_filter($row, fn ($value) => $value !== '')) {
                $rows[] = $row;
            }
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsxRows(string $path): array
    {
        $zip = new \ZipArchive();

        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Unable to read uploaded XLSX file.');
        }

        $sharedStrings = $this->readXlsxSharedStrings($zip);
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $workbookRelsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        $sheetPath = 'xl/worksheets/sheet1.xml';

        if ($workbookXml && $workbookRelsXml) {
            $workbook = simplexml_load_string($workbookXml);
            $rels = simplexml_load_string($workbookRelsXml);
            $workbook->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $rels->registerXPathNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');
            $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $sheets = $workbook->xpath('//x:sheets/x:sheet') ?: [];
            $firstSheet = $sheets[0] ?? null;
            $relationshipId = $firstSheet ? (string) $firstSheet->attributes('r', true)->id : null;

            if ($relationshipId) {
                foreach (($rels->xpath('//rel:Relationship') ?: []) as $relationship) {
                    if ((string) $relationship['Id'] === $relationshipId) {
                        $target = ltrim((string) $relationship['Target'], '/');
                        $sheetPath = str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
                        break;
                    }
                }
            }
        }

        $sheetXml = $zip->getFromName($sheetPath);
        $zip->close();

        if (! $sheetXml) {
            throw new \RuntimeException('The XLSX file does not contain a readable first sheet.');
        }

        $sheet = simplexml_load_string($sheetXml);
        $sheet->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $headers = [];
        $rows = [];

        foreach (($sheet->xpath('//x:sheetData/x:row') ?: []) as $rowIndex => $rowNode) {
            $rowNode->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $values = [];

            foreach (($rowNode->xpath('x:c') ?: []) as $cell) {
                $cellRef = (string) $cell['r'];
                $column = preg_replace('/\d+/', '', $cellRef);
                $values[$column] = $this->readXlsxCellValue($cell, $sharedStrings);
            }

            if ($rowIndex === 0) {
                foreach ($values as $column => $value) {
                    $headers[$column] = $this->normalizeImportHeader($value);
                }
                continue;
            }

            $mapped = [];
            foreach ($headers as $column => $header) {
                if ($header !== '') {
                    $mapped[$header] = trim((string) ($values[$column] ?? ''));
                }
            }

            if (array_filter($mapped, fn ($value) => $value !== '')) {
                $rows[] = $mapped;
            }
        }

        return $rows;
    }

    private function readXlsxSharedStrings(\ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if (! $xml) {
            return [];
        }

        $strings = [];
        $shared = simplexml_load_string($xml);
        $shared->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        foreach (($shared->xpath('//x:si') ?: []) as $item) {
            $item->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $text = '';

            foreach (($item->xpath('.//x:t') ?: []) as $textNode) {
                $text .= (string) $textNode;
            }

            $strings[] = $text;
        }

        return $strings;
    }

    private function readXlsxCellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];
        $cell->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        if ($type === 'inlineStr') {
            $inlineText = $cell->xpath('x:is/x:t');

            return trim((string) ($inlineText[0] ?? ''));
        }

        $values = $cell->xpath('x:v');
        $value = (string) ($values[0] ?? '');

        if ($type === 's') {
            return trim($sharedStrings[(int) $value] ?? '');
        }

        return trim($value);
    }

    private function normalizeImportHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);
        $header = trim((string) $header, '_');

        return match ($header) {
            'first_name', 'fname' => 'firstname',
            'middle_name', 'mname' => 'middlename',
            'last_name', 'lname' => 'lastname',
            'type', 'philcst_user_type' => 'member_type',
            'student_number', 'student_no' => 'student_id',
            'employee_number', 'employee_no' => 'employee_id',
            default => $header,
        };
    }

    private function normalizeImportedUserRow(array $row): array
    {
        $fullName = trim((string) ($row['name'] ?? ''));
        $nameParts = preg_split('/\s+/', $fullName) ?: [];
        $studentId = trim((string) ($row['student_id'] ?? ''));

        if ($studentId !== '' && ctype_digit($studentId) && strlen($studentId) < 8) {
            $studentId = str_pad($studentId, 8, '0', STR_PAD_LEFT);
        }

        return [
            'firstname' => trim((string) ($row['firstname'] ?? ($nameParts[0] ?? ''))),
            'middlename' => trim((string) ($row['middlename'] ?? (count($nameParts) > 2 ? $nameParts[1] : ''))),
            'lastname' => trim((string) ($row['lastname'] ?? (count($nameParts) > 1 ? end($nameParts) : ''))),
            'email' => trim((string) ($row['email'] ?? '')),
            'password' => trim((string) ($row['password'] ?? '')),
            'role' => strtolower(trim((string) ($row['role'] ?? ''))),
            'department' => trim((string) ($row['department'] ?? '')),
            'member_type' => strtolower(trim((string) ($row['member_type'] ?? 'student'))),
            'student_id' => $studentId,
            'employee_id' => trim((string) ($row['employee_id'] ?? '')),
            'year_level' => trim((string) ($row['year_level'] ?? '')),
        ];
    }

    private function validateImportedUserRow(array $row): array
    {
        $errors = [];

        foreach (['firstname' => 'First name', 'lastname' => 'Last name', 'middlename' => 'Middle name'] as $field => $label) {
            if ($row[$field] === '') {
                $errors[] = $label . ' is required.';
            } elseif (! preg_match('/^[a-zA-Z\s]+$/', $row[$field])) {
                $errors[] = $label . ' must contain letters only.';
            }
        }

        if (! in_array($row['member_type'], ['student', 'faculty'], true)) {
            $errors[] = 'Member type must be student or faculty.';
        }

        if ($row['role'] && ! in_array($row['role'], ['user', 'researcher'], true)) {
            $errors[] = 'Role must be user or researcher.';
        }

        if (! $this->isDepartmentScoped() && $row['department'] === '') {
            $errors[] = 'Department is required.';
        }

        if ($row['member_type'] === 'student' && $row['student_id'] === '') {
            $errors[] = 'Student ID is required.';
        }

        if ($row['member_type'] === 'faculty' && $row['employee_id'] === '') {
            $errors[] = 'Employee ID is required.';
        }

        if ($row['year_level'] !== '' && ! in_array((int) $row['year_level'], [1, 2, 3, 4], true)) {
            $errors[] = 'Year level must be 1, 2, 3, or 4.';
        }

        return $errors;
    }

    private function generateImportedUserEmail(?string $identifier): string
    {
        return strtolower(($identifier ?: 'user') . '.' . date('Y') . '@ube.local');
    }

    private function generateImportedUserPassword(?string $identifier, string $firstname): string
    {
        $passwordSuffix = ucfirst(strtolower(substr(trim($firstname), 0, 3)));

        return ($identifier ?: 'user') . '_' . $passwordSuffix;
    }

    private function formatImportedUserName(array $row): string
    {
        $middleInitial = strtoupper(substr(trim($row['middlename']), 0, 1)) . '.';

        return trim(implode(' ', array_filter([
            $row['firstname'],
            $middleInitial,
            $row['lastname'],
        ])));
    }

    public function reports(Request $request)
    {
        $departments = [
            'College of Accountancy and Business Education',
            'College of Computer Studies',
            'College of Criminal Justice Education',
            'College of Education',
            'College of Engineering and Architecture',
            'College of Maritime Studies',
        ];

        if ($department = $this->adminDepartment()) {
            $departments = [$department];
        }

        $query = $this->scopeResearchQuery(Research::approved());

        if ($this->currentAdmin()->isGlobalAdmin() && $dept = $request->get('department')) {
            $query->where('department', $dept);
        }
        if ($year = $request->get('year')) {
            $query->where('year_published', $year);
        }
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        $reportBaseQuery = $query
            ->whereBetween('year_published', [self::MIN_RESEARCH_YEAR, self::MAX_RESEARCH_YEAR]);

        $departmentTotals = (clone $reportBaseQuery)
            ->select('department')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('department')
            ->get()
            ->groupBy(fn ($row) => $this->normalizedDepartmentName($row->department))
            ->map(fn ($rows) => (int) $rows->sum('count'));

        $researches = $reportBaseQuery
            ->select([
                'id',
                'title',
                'author_name',
                'department',
                'course',
                'program',
                'type',
                'year_published',
            ])
            ->orderBy('department')
            ->orderBy('year_published', 'desc')
            ->paginate(self::REPORTS_PER_PAGE)
            ->withQueryString();
        $years = collect(range(self::MAX_RESEARCH_YEAR, self::MIN_RESEARCH_YEAR));

        return view('admin.reports', compact('researches', 'departments', 'years', 'departmentTotals'));
    }

    public function captureAttemptLogs(Request $request)
    {
        abort_if($this->currentAdmin()->isDepartmentDean(), 403, 'Department deans cannot access capture logs.');

        $securityEventTypes = CaptureAttemptLog::securityEventTypes();
        $query = $this->scopeCaptureLogQuery(CaptureAttemptLog::with(['research', 'user'])->latest());

        if ($search = $request->get('search')) {
            $query->where(function ($inner) use ($search) {
                $inner->where('viewer_name', 'like', "%{$search}%")
                    ->orWhere('viewer_email', 'like', "%{$search}%")
                    ->orWhere('event_type', 'like', "%{$search}%")
                    ->orWhereHas('research', function ($researchQuery) use ($search) {
                        $researchQuery->where('title', 'like', "%{$search}%");
                    });
            });
        }

        if ($eventType = $request->get('event_type')) {
            $query->where('event_type', $eventType);
        } else {
            $query->whereIn('event_type', $securityEventTypes);
        }

        if ($department = $request->get('department')) {
            $query->where(function ($inner) use ($department) {
                $inner->where('viewer_department', $department)
                    ->orWhereHas('research', fn ($researchQuery) => $researchQuery->where('department', $department));
            });
        }

        if ($date = $request->get('date')) {
            $startOfDay = \Carbon\Carbon::parse($date, 'Asia/Manila')
                ->startOfDay()
                ->timezone('UTC');
            $endOfDay = \Carbon\Carbon::parse($date, 'Asia/Manila')
                ->endOfDay()
                ->timezone('UTC');

            $query->whereBetween('created_at', [$startOfDay, $endOfDay]);
        }

        $activityLogs = (clone $query)
            ->limit(300)
            ->get();
        $readLogIds = $this->readCaptureLogIdsFor($this->currentAdmin(), $activityLogs->pluck('id')->all());

        $logs = $query->paginate(20)->withQueryString();
        $eventTypes = CaptureAttemptLog::adminFilterEventTypes();
        $researchDepartments = $this->scopeResearchQuery(Research::query())
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department');
        $logDepartments = $this->scopeCaptureLogQuery(CaptureAttemptLog::query())
            ->whereNotNull('viewer_department')
            ->distinct()
            ->pluck('viewer_department');
        $departments = $researchDepartments
            ->merge($logDepartments)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('admin.capture-attempt-logs', compact('logs', 'activityLogs', 'eventTypes', 'departments', 'readLogIds', 'securityEventTypes'));
    }

    public function captureAttemptSummary(Request $request)
    {
        $user = $this->currentAdmin();
        abort_if($user->isDepartmentDean(), 403, 'Department deans cannot access capture logs.');

        $query = $this->scopeCaptureLogQuery(
            CaptureAttemptLog::with(['research', 'user'])
                ->whereIn('event_type', CaptureAttemptLog::securityEventTypes())
        );

        $latest = $this->unreadCaptureLogQuery($user, clone $query)->latest('id')->first();
        $unreadCount = $this->unreadCaptureLogQuery($user, clone $query)->count();

        return response()->json([
            'latest_id' => $latest?->id,
            'unread_count' => $unreadCount,
            'event' => $this->captureLogEventLabel($latest?->event_type),
            'viewer' => $latest?->viewer_name ?: $latest?->user?->name ?: 'Unknown viewer',
            'research' => $latest?->research?->title ?: 'Protected viewer',
            'created_at' => $latest?->created_at?->copy()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);
    }

    public function markCaptureActivityViewed(Request $request)
    {
        abort_if($this->currentAdmin()->isDepartmentDean(), 403, 'Department deans cannot access capture logs.');

        $data = $request->validate([
            'log_ids' => ['required', 'array', 'min:1'],
            'log_ids.*' => ['integer'],
        ]);

        $visibleLogIds = $this->scopeCaptureLogQuery(CaptureAttemptLog::query())
            ->whereIn('id', $data['log_ids'])
            ->whereIn('event_type', CaptureAttemptLog::securityEventTypes())
            ->pluck('id')
            ->values();

        if ($visibleLogIds->isNotEmpty()) {
            $now = now();
            $rows = $visibleLogIds->map(fn ($logId) => [
                'user_id' => $this->currentAdmin()->id,
                'capture_attempt_log_id' => $logId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            DB::table('capture_log_reads')->upsert(
                $rows,
                ['user_id', 'capture_attempt_log_id'],
                ['updated_at']
            );
        }

        $unreadCount = $this->unreadCaptureLogQuery(
            $this->currentAdmin(),
            $this->scopeCaptureLogQuery(CaptureAttemptLog::query()->whereIn('event_type', CaptureAttemptLog::securityEventTypes()))
        )->count();

        return response()->json([
            'ok' => true,
            'unread_count' => $unreadCount,
            'read_log_ids' => $visibleLogIds,
        ]);
    }

    // ── Researcher Accounts ─────────────────────────────────────────────────

    public function researcherAccounts()
    {
        $currentYear = (int) date('Y');
        $researchers = $this->scopeUserQuery(User::where('role', 'researcher'))
            ->where('is_approved', true)
            ->withCount('researches')
            ->latest()
            ->paginate(20);
        return view('admin.researcher-accounts', compact('researchers', 'currentYear'));
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

    private function captureLogEventLabel(?string $eventType): string
    {
        return match ($eventType) {
            'protected_view_opened' => 'Protected View Opened',
            'printscreen' => 'Screenshot Attempt',
            'print_blocked' => 'Print Blocked',
            'save_blocked' => 'Save Blocked',
            'copy_blocked' => 'Copy Blocked',
            'source_view_blocked' => 'Source View Blocked',
            'window_blur' => 'Window Switched',
            'tab_hidden' => 'Tab Hidden',
            'context_menu_blocked' => 'Right Click Blocked',
            default => $eventType ? str_replace('_', ' ', ucwords($eventType, '_')) : 'Security Event',
        };
    }

    private function readCaptureLogIdsFor(User $user, array $logIds): array
    {
        if (empty($logIds)) {
            return [];
        }

        return CaptureLogRead::query()
            ->where('user_id', $user->id)
            ->whereIn('capture_attempt_log_id', $logIds)
            ->pluck('capture_attempt_log_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function unreadCaptureLogQuery(User $user, $query)
    {
        return $query->whereDoesntHave('reads', function ($readQuery) use ($user) {
            $readQuery->where('user_id', $user->id);
        });
    }
}
