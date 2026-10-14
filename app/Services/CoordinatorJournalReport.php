<?php

namespace App\Services;

use App\Models\Research;
use App\Models\ResearchHandoff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class CoordinatorJournalReport
{
    public const STATUSES = [
        'pending' => 'Pending',
        'preparing' => 'Preparing Journals',
        'submitted' => 'Submitted to Dean',
    ];

    public const HEADERS = ['Academic Year', 'Program', 'Journal Category', 'No. of Journals', 'Status', 'Date Submitted'];

    public const METRICS = [
        'tasks' => 'Total Assigned Journal Preparation Tasks',
        'journals' => 'Total Journals Prepared',
        'batches' => 'Total Journal Batches',
        'submitted' => 'Total Batches Submitted to Dean',
    ];

    public function build(User $coordinator, Request $request): array
    {
        $categories = Research::adminSubmissionCategories();
        $validated = $request->validate([
            'school_year' => ['nullable', 'string', 'max:30'],
            'program' => ['nullable', 'string', 'max:255'],
            'submission_category' => ['nullable', Rule::in(array_keys($categories))],
            'status' => ['nullable', Rule::in(array_keys(self::STATUSES))],
        ]);
        $filters = array_replace(array_fill_keys(['school_year', 'program', 'submission_category', 'status'], ''), $validated);

        // Count prepared journals, never the source papers in journal_batch_items.
        $journals = fn ($query) => $query->where('department', $coordinator->department)
            ->where('coordinator_id', $coordinator->id)
            ->whereIn('submission_category', array_keys($categories));
        $tasks = ResearchHandoff::query()
            ->where('department', $coordinator->department)
            ->where('coordinator_id', $coordinator->id)
            ->where(fn ($query) => $query->whereIn('submission_category', array_keys($categories))
                ->orWhereHas('research', $journals))
            ->with(['research' => $journals, 'journals' => $journals])
            ->withCount(['journals' => $journals])
            ->orderByDesc('school_year')->orderByDesc('id')->get();

        $allRows = $tasks->map(function (ResearchHandoff $task) use ($categories) {
            $legacy = $task->is_batch ? null : $task->research;
            $category = $legacy?->submission_category ?: $task->submission_category;
            if (! isset($categories[$category])) {
                return null;
            }
            $journalCount = $task->is_batch ? $task->journals_count : ($legacy ? 1 : 0);
            $stage = $task->is_batch ? $task->batch_stage : $legacy?->status;
            $submittedAt = $task->is_batch ? $task->batch_submitted_at : $legacy?->submitted_to_dean_at;

            // Later Dean/Admin processing does not undo the coordinator's completed submission.
            // Returned journals are back in preparation, even if a previous submission date remains.
            $submitted = $stage !== Research::STATUS_REJECTED && (
                $submittedAt !== null || in_array($stage, [Research::STATUS_DEAN_REVIEW,
                    Research::STATUS_DEAN_APPROVED, Research::STATUS_PENDING, Research::STATUS_APPROVED], true)
            );
            $status = $submitted ? 'submitted' : (
                $task->status !== ResearchHandoff::STATUS_PENDING || $journalCount > 0 || $stage === Research::STATUS_REJECTED
                    ? 'preparing' : 'pending'
            );
            $program = $task->program ?: ($legacy?->program ?: $legacy?->course);
            $programKey = $program ?: ($category === Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL ? '__all_programs' : '__unassigned');

            return [
                'id' => $task->id,
                'school_year' => $task->school_year ?: ($legacy?->school_year ?: 'Not assigned'),
                'program_key' => $programKey,
                'program' => $program ?: ($programKey === '__all_programs' ? 'All Programs' : 'Not assigned'),
                'submission_category' => $category,
                'category' => $categories[$category],
                'journals' => (int) $journalCount,
                'is_batch' => $task->is_batch,
                'status_key' => $status,
                'status' => self::STATUSES[$status],
                'submitted_at' => $submitted && $submittedAt ? $submittedAt->copy()->timezone('Asia/Manila')->format('M d, Y') : '—',
            ];
        })->filter()->values();

        $rows = $allRows->filter(fn ($row) => (! $filters['school_year'] || $row['school_year'] === $filters['school_year']) &&
            (! $filters['program'] || $row['program_key'] === $filters['program']) &&
            (! $filters['submission_category'] || $row['submission_category'] === $filters['submission_category']) &&
            (! $filters['status'] || $row['status_key'] === $filters['status'])
        )->values();

        $breakdowns = collect([
            'school_year' => 'Academic Year', 'program' => 'Program',
            'category' => 'Journal Category', 'status' => 'Status',
        ])->map(fn ($title, $key) => [
            'title' => $title,
            'rows' => $rows->groupBy($key)->map(fn ($group, $label) => ['label' => $label] + $this->totals($group))
                ->sortBy('label', SORT_NATURAL, $key === 'school_year')->values(),
        ]);
        $programs = collect(Research::programsByDepartment()[$coordinator->department] ?? [])
            ->mapWithKeys(fn ($program) => [$program => $program])->merge($allRows->pluck('program', 'program_key'))->sort();

        return compact('rows', 'filters', 'categories', 'breakdowns', 'programs') + [
            'totals' => $this->totals($rows),
            'schoolYears' => $allRows->pluck('school_year')->unique()->sortDesc()->values(),
            'statuses' => self::STATUSES,
            'coordinator' => $coordinator,
            'generatedAt' => now('Asia/Manila')->format('M d, Y h:i A'),
            'filterLabels' => [
                'Academic Year' => $filters['school_year'] ?: 'All Academic Years',
                'Program' => $programs->get($filters['program'], $filters['program'] ?: 'All Programs'),
                'Journal Category' => $categories[$filters['submission_category']] ?? 'All Categories',
                'Status' => self::STATUSES[$filters['status']] ?? 'All Statuses',
            ],
        ];
    }

    private function totals(Collection $rows): array
    {
        return [
            'tasks' => $rows->count(),
            'journals' => (int) $rows->sum('journals'),
            'batches' => $rows->where('is_batch', true)->count(),
            'submitted' => $rows->where('is_batch', true)->where('status_key', 'submitted')->count(),
        ];
    }

    public static function cells(array $row): array
    {
        return [$row['school_year'], $row['program'], $row['category'], $row['journals'], $row['status'], $row['submitted_at']];
    }
}
