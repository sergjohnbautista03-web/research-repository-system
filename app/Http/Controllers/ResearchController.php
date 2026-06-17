<?php

namespace App\Http\Controllers;

use App\Models\CaptureAttemptLog;
use App\Models\Research;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ResearchController extends Controller
{
    private const MIN_RESEARCH_YEAR = 2022;
    private const MAX_RESEARCH_YEAR = 2026;

    private const AUDIT_TIMEZONE = 'Asia/Manila';

    private function renderProtectedViewer(Request $request, Research $research, bool $adminMode = false)
    {
        $user = auth()->user();

        if ($adminMode) {
            if (! $user || ! $user->isAdmin()) {
                abort(403, 'Admins only.');
            }
        } else {
            if (! $user) {
                return redirect()->route('login')->with('error', 'Login required.');
            }

            if ($research->status !== 'approved') {
                if (! $user || (! $user->isAdmin() && auth()->id() !== $research->user_id)) {
                    abort(404);
                }
            }

            if (! $this->canAccessFullDocument($request, $user)) {
                return redirect()->route('research.show', $research)
                    ->with('error', 'Your account is not allowed to view the full document.');
            }
        }

        if (! Storage::disk('public')->exists($research->file_path)) {
            abort(404);
        }

        $signedUrl = URL::temporarySignedRoute(
            'research.streamPdf',
            now()->addMinutes(15),
            [
                'research' => $research->id,
                'scope' => $adminMode ? 'admin' : 'standard',
            ]
        );

        return view('research.protected-viewer', compact('research', 'signedUrl', 'adminMode'));
    }

    private function viewerIdentityPayload(Request $request, ?object $user, bool $adminMode = false): array
    {
        return [
            'name' => $user?->name ?: 'Guest Viewer',
            'email' => $user?->email,
            'department' => $user?->department,
            'scope' => $adminMode ? 'admin' : 'standard',
        ];
    }

    private function canAccessFullDocument(Request $request, $user): bool
    {
        return $user?->canViewFullDocument() === true;
    }

    private function auditDayWindow(): array
    {
        $now = now(self::AUDIT_TIMEZONE);

        return [
            $now->copy()->startOfDay()->timezone('UTC'),
            $now->copy()->endOfDay()->timezone('UTC'),
        ];
    }

    private function recordCaptureAttemptForToday(Request $request, Research $research, ?User $user, array $payload, array $identity): CaptureAttemptLog
    {
        [$startOfDay, $endOfDay] = $this->auditDayWindow();

        $existingLogQuery = CaptureAttemptLog::query()
            ->where('research_id', $research->id)
            ->where('event_type', $payload['event_type'])
            ->where('viewer_scope', $identity['scope'])
            ->whereBetween('created_at', [$startOfDay, $endOfDay]);

        if ($user) {
            $existingLogQuery->where('user_id', $user->id);
        } else {
            $existingLogQuery
                ->whereNull('user_id')
                ->where('viewer_email', $identity['email'])
                ->where('ip_address', $request->ip());
        }

        $existingLog = $existingLogQuery->first();

        $attributes = [
            'research_id' => $research->id,
            'user_id' => $user?->id,
            'event_type' => $payload['event_type'],
            'viewer_name' => $identity['name'],
            'viewer_email' => $identity['email'],
            'viewer_department' => $identity['department'],
            'viewer_scope' => $identity['scope'],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'details' => $payload['details'] ?? null,
        ];

        if ($existingLog) {
            $existingLog->fill($attributes);
            $existingLog->save();

            return $existingLog;
        }

        return CaptureAttemptLog::create($attributes);
    }

    public function index(Request $request)
    {
        $query = Research::approved()
            ->with('user')
            ->whereBetween('year_published', [self::MIN_RESEARCH_YEAR, self::MAX_RESEARCH_YEAR]);

        if ($search = $request->get('search')) {
            $query->search($search);
        }

        if ($field = $request->get('field')) {
            if ($field === 'title') {
                $query->where('title', 'like', '%' . $request->search . '%');
            } elseif ($field === 'author') {
                $query->where('author_name', 'like', '%' . $request->search . '%');
            } elseif ($field === 'abstract') {
                $query->where('abstract', 'like', '%' . $request->search . '%');
            }
        }

        if ($dept = $request->get('department')) {
            $query->where('department', $dept);
        }

        if ($yearFrom = $request->integer('year_from')) {
            $yearFrom = max(self::MIN_RESEARCH_YEAR, min(self::MAX_RESEARCH_YEAR, $yearFrom));
            $query->where('year_published', '>=', $yearFrom);
        }

        if ($yearTo = $request->integer('year_to')) {
            $yearTo = max(self::MIN_RESEARCH_YEAR, min(self::MAX_RESEARCH_YEAR, $yearTo));
            $query->where('year_published', '<=', $yearTo);
        }

        $researches = $query->orderBy('created_at', 'desc')->paginate(12)->withQueryString();
        $departments = Research::approved()
            ->whereBetween('year_published', [self::MIN_RESEARCH_YEAR, self::MAX_RESEARCH_YEAR])
            ->distinct()
            ->pluck('department')
            ->sort()
            ->values();
        $featured = Research::approved()
            ->whereBetween('year_published', [self::MIN_RESEARCH_YEAR, self::MAX_RESEARCH_YEAR])
            ->orderBy('view_count', 'desc')
            ->take(6)
            ->get();

        return view('home', compact('researches', 'departments', 'featured'));
    }

    public function show(Research $research)
    {
        if ($research->status !== 'approved') {
            if (! auth()->check() || auth()->id() !== $research->user_id) {
                abort(404);
            }
        }

        $research->increment('view_count');

        return view('research.show', compact('research'));
    }

    public function submitForm()
    {
        $user = auth()->user();

        if ($user->isGraduated()) {
            return redirect()->route('home')
                ->with('error', 'Graduated researchers cannot submit.');
        }

        if (! $user->isAdmin() && ! $user->canSubmitResearch()) {
            return redirect()->route('user.dashboard')
                ->with('error', 'Only active approved accounts can submit research.');
        }

        return view('research.submit');
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if ($user->isGraduated()) {
            return redirect()->back()->with('error', 'Graduated users cannot submit.');
        }

        if (! $user->isAdmin() && ! $user->canSubmitResearch()) {
            return redirect()->route('user.dashboard')->with('error', 'Only active approved accounts can submit research.');
        }

        if ($user->isDepartmentDean() && empty($user->department)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Your dean account does not have an assigned department.');
        }

        $data = $request->validate([
            'title'          => ['required', 'string', 'min:10', 'max:500'],
            'submission_category' => ['required', Rule::in(array_keys(Research::submissionCategories()))],
            'type'           => ['required', 'string'],
            'author_name'    => 'required|string|max:255',
            'department'     => [$user->isDepartmentDean() ? 'nullable' : 'required', 'string', 'max:255'],
            'course'         => 'required|string|max:255',
            'year_published' => 'required|integer|min:' . self::MIN_RESEARCH_YEAR . '|max:' . self::MAX_RESEARCH_YEAR,
            'keywords'       => 'nullable|string|max:500',
            'abstract'       => 'required|string',
            'file'           => 'required|file|mimes:pdf|max:30720',
        ]);

        if ($user->isDepartmentDean()) {
            $data['department'] = $user->department;
        }

        validator($data, [
            'type' => [Rule::in(Research::typesForCategory($data['submission_category']))],
        ])->validate();

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('researches', $fileName, 'public');

        Research::create([
            'title'          => $data['title'],
            'submission_category' => $data['submission_category'],
            'type'           => $data['type'],
            'author_name'    => $data['author_name'],
            'user_id'        => Auth::id(),
            'department'     => $data['department'],
            'course'         => $data['course'],
            'year_published' => $data['year_published'],
            'keywords'       => $data['keywords'],
            'abstract'       => $data['abstract'],
            'file_path'      => $filePath,
            'file_name'      => $file->getClientOriginalName(),
            'status'         => 'pending',
        ]);

        return redirect()->route('home')->with('success', ucfirst($data['submission_category']) . ' submitted successfully!');
    }

    public function viewFile(Research $research)
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Login required.');
        }

        if ($research->status !== 'approved') {
            if (! $user || (! $user->isAdmin() && auth()->id() !== $research->user_id)) {
                abort(404);
            }
        }

        if (! Storage::disk('public')->exists($research->file_path)) {
            abort(404);
        }

        if (! $this->canAccessFullDocument(request(), $user)) {
            return redirect()->route('research.show', $research)
                ->with('error', 'Your account is not allowed to view the full document.');
        }

        return $this->renderProtectedViewer(request(), $research, false);
    }

    public function protectedView(Request $request, Research $research)
    {
        return $this->renderProtectedViewer($request, $research, false);
    }

    public function logCaptureAttempt(Request $request, Research $research)
    {
        $user = auth()->user();

        $scope = $request->input('scope') === 'admin' ? 'admin' : 'standard';

        if ($scope === 'admin') {
            if (! $user || ! $user->isAdmin()) {
                abort(403, 'Admins only.');
            }
        } else {
            if ($research->status !== 'approved') {
                if (! $user || (! $user->isAdmin() && auth()->id() !== $research->user_id)) {
                    abort(404);
                }
            }

            if (! $this->canAccessFullDocument($request, $user)) {
                abort(403, 'You are not allowed to view this file.');
            }
        }

        $payload = $request->validate([
            'event_type' => ['required', 'string', 'max:60'],
            'scope' => ['nullable', 'in:standard,admin'],
            'details' => ['nullable', 'array'],
        ]);

        $allowedEvents = CaptureAttemptLog::securityEventTypes();

        abort_unless(in_array($payload['event_type'], $allowedEvents, true), 422, 'Unsupported log event.');

        $identity = $this->viewerIdentityPayload($request, $user, $scope === 'admin');
        $log = $this->recordCaptureAttemptForToday($request, $research, $user, $payload, $identity);

        return response()->json(['ok' => true, 'log_id' => $log->id]);
    }

    public function streamPdf(Request $request, Research $research)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This link has expired or is invalid. Please go back and try again.');
        }

        $scope = $request->query('scope', 'standard');
        $user = auth()->user();

        if ($scope === 'admin') {
            if (! $user || ! $user->isAdmin()) {
                abort(403, 'Admins only.');
            }
        } else {
            if ($research->status !== 'approved') {
                abort(404);
            }

            if (! $this->canAccessFullDocument($request, $user)) {
                abort(403, 'You are not allowed to view this file.');
            }
        }

        if (! Storage::disk('public')->exists($research->file_path)) {
            abort(404);
        }

        $path = Storage::disk('public')->path($research->file_path);

        return response()->file($path, [
            'Content-Type'           => 'application/pdf',
            'Content-Disposition'    => 'inline; filename="' . $research->file_name . '"',
            'Cache-Control'          => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'                 => 'no-cache',
            'Expires'                => '0',
            'X-Frame-Options'        => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function adminViewFile(Request $request, Research $research)
    {
        return $this->renderProtectedViewer($request, $research, true);
    }

    public function mySubmissions()
    {
        $researches = Research::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('research.my-submissions', compact('researches'));
    }

    public function pinToggle(Research $research)
    {
        $user = Auth::user();

        if ($user->pinnedResearches()->where('research_id', $research->id)->exists()) {
            $user->pinnedResearches()->detach($research->id);
            $pinned = false;
        } else {
            $user->pinnedResearches()->attach($research->id);
            $pinned = true;
        }

        return response()->json(['pinned' => $pinned]);
    }

    public function pinned()
    {
        $researches = Auth::user()->pinnedResearches()->paginate(10);

        return view('research.pinned', compact('researches'));
    }

    public function userDashboard()
    {
        $user = auth()->user();

        abort_if(! $user || $user->isAdmin(), 403);

        $pinnedResearches = $user->pinnedResearches()
            ->latest('research_pins.created_at')
            ->get();
        $mySubmissions = $user->researches()
            ->latest()
            ->get();

        $stats = [
            'pinned' => $pinnedResearches->count(),
            'submissions' => $mySubmissions->count(),
            'department_researches' => $user->department
                ? Research::approved()
                    ->where('department', $user->department)
                    ->whereBetween('year_published', [self::MIN_RESEARCH_YEAR, self::MAX_RESEARCH_YEAR])
                    ->count()
                : 0,
        ];

        $recentPinned = $pinnedResearches->take(4);
        $recentDepartmentResearches = $user->department
            ? Research::approved()
                ->where('department', $user->department)
                ->whereBetween('year_published', [self::MIN_RESEARCH_YEAR, self::MAX_RESEARCH_YEAR])
                ->latest()
                ->take(4)
                ->get()
            : collect();
        $departments = $this->departments();

        return view('user.dashboard', compact('user', 'stats', 'pinnedResearches', 'mySubmissions', 'recentPinned', 'recentDepartmentResearches', 'departments'));
    }

    public function byDepartment($department)
    {
        $department = rawurldecode($department);

        $researches = Research::approved()
            ->where('department', $department)
            ->whereBetween('year_published', [self::MIN_RESEARCH_YEAR, self::MAX_RESEARCH_YEAR])
            ->paginate(12);

        return view('research.department', compact('researches', 'department'));
    }

    public function byCourse($department, $course)
    {
        $department = rawurldecode($department);
        $course = rawurldecode($course);

        $researches = Research::approved()
            ->where('department', $department)
            ->where('course', $course)
            ->whereBetween('year_published', [self::MIN_RESEARCH_YEAR, self::MAX_RESEARCH_YEAR])
            ->paginate(12);

        return view('research.department', compact('researches', 'department', 'course'));
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
