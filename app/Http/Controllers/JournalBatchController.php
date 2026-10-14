<?php

namespace App\Http\Controllers;

use App\Models\Research;
use App\Models\ResearchHandoff;
use App\Services\JournalBatchWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class JournalBatchController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();
        $query = ResearchHandoff::where('is_batch', true)->with(['dean', 'coordinator'])->withCount('journals');
        if ($actor->isGlobalAdmin()) {
            $query->whereIn('batch_stage', [Research::STATUS_PENDING, Research::STATUS_APPROVED])
                ->whereNotNull('batch_forwarded_at');
        } else {
            abort_unless($actor->isDepartmentScopedAdmin() && filled($actor->department), 403);
            $query->where('department', $actor->department)
                ->where($actor->isDepartmentDean() ? 'dean_id' : 'coordinator_id', $actor->id);
            $query->whereIn('batch_stage', [Research::STATUS_DEAN_REVIEW, Research::STATUS_DEAN_APPROVED,
                Research::STATUS_PENDING, Research::STATUS_APPROVED]);
        }
        $schoolYears = collect(range(2022, Research::maxPublicationYear()))
            ->map(fn ($year) => $year.'-'.($year + 1))
            ->merge((clone $query)->distinct()->pluck('school_year'))
            ->filter()->unique()->sortDesc()->values();
        $programCatalog = collect(Research::programsByDepartment());
        $departments = $actor->isGlobalAdmin()
            ? collect(config('research.departments', $programCatalog->keys()->all()))
            : collect([$actor->department]);
        $programsByDepartment = $departments->mapWithKeys(fn ($department) => [
            $department => collect(['Information Technology', 'Computer Science'])
                ->intersect($programCatalog->get($department, []))
                ->merge($programCatalog->get($department, []))->unique()->values()->all(),
        ]);
        $department = $actor->isDepartmentDean() ? null : $request->query('department');
        $programs = $department
            ? collect($programsByDepartment->get($department, []))
            : $programsByDepartment->flatten()->unique()->values();
        if (filled($department)) {
            $query->where('department', $department);
        }
        if ($request->filled('school_year')) {
            $query->where('school_year', $request->input('school_year'));
        }
        if ($request->filled('program')) {
            $query->where('program', $request->input('program'));
        }

        return view('admin.journal-batches', ['batches' => $query->latest()->paginate(12)->withQueryString(),
            'schoolYears' => $schoolYears, 'programs' => $programs, 'departments' => $departments,
            'programsByDepartment' => $programsByDepartment]);
    }

    public function show(Request $request, ResearchHandoff $batch, JournalBatchWorkflow $workflow)
    {
        $workflow->authorize($batch, $request->user());
        $batch->load(['dean', 'coordinator', 'journals' => fn ($query) => $query->orderBy('id')]);
        $editable = $request->user()->isResearchCoordinator()
            && in_array($batch->batch_stage, [Research::STATUS_DRAFT, Research::STATUS_REJECTED], true);
        $editingJournal = null;
        if ($request->filled('edit') || $request->boolean('add')) {
            $workflow->ensureEditable($batch, $request->user());
            if ($request->filled('edit')) {
                $editingJournal = $batch->journals()->findOrFail($request->input('edit'));
            }
        }

        $embedded = $request->boolean('embedded');

        return view('admin.journal-batch', compact('batch', 'editable', 'editingJournal', 'embedded'));
    }

    public function storeJournal(Request $request, ResearchHandoff $batch, JournalBatchWorkflow $workflow, ?Research $journal = null)
    {
        $workflow->ensureEditable($batch, $request->user());
        if ($journal) {
            abort_unless($journal->research_handoff_id === $batch->id, 404);
        }
        $data = $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'authors' => ['required', 'array', 'min:1', 'max:12'],
            'authors.*' => ['required', 'string', 'max:150'],
            'abstract' => ['required', 'string'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::in(Research::journalTypeOptions())],
            'submission_category' => ['required', Rule::in(array_keys(Research::adminSubmissionCategories()))],
            'year_published' => ['required', 'integer', 'min:2022', 'max:'.Research::maxPublicationYear()],
            'course' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:30720'],
            'school_year' => ['prohibited'], 'department' => ['prohibited'],
        ]);
        $authors = collect($data['authors'])->map(fn ($name) => trim($name))->filter()->values();
        if ($authors->isEmpty()) {
            throw ValidationException::withMessages(['authors' => 'Add at least one author.']);
        }
        if ($batch->program && $request->filled('course') && $data['course'] !== $batch->program) {
            throw ValidationException::withMessages(['course' => 'Use the program assigned by the Dean.']);
        }
        $program = $batch->program ?? ($data['course'] ?? null);
        if ($data['submission_category'] === Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL
            && ! in_array($program ?? '', Research::programsByDepartment()[$batch->department] ?? [], true)) {
            throw ValidationException::withMessages(['course' => 'Choose a valid program for this department.']);
        }
        $path = $request->file('file')?->store('researches', 'public');
        $oldPath = $journal?->file_path;
        try {
            DB::transaction(function () use ($batch, $journal, $workflow, $request, $data, $authors, $path) {
                $batch = ResearchHandoff::whereKey($batch->id)->lockForUpdate()->firstOrFail();
                $workflow->ensureEditable($batch, $request->user());
                $record = $journal ? $batch->journals()->lockForUpdate()->findOrFail($journal->id) : new Research;
                $record->fill([
                    'title' => $data['title'], 'abstract' => $data['abstract'], 'authors' => $authors->all(),
                    'keywords' => $data['keywords'] ?? null,
                    'author_name' => $authors->implode('; '), 'type' => $data['type'],
                    'submission_category' => $data['submission_category'], 'issn' => Research::issnForSubmissionCategory($data['submission_category']),
                    'course' => $batch->program ?? ($data['course'] ?? null), 'program' => $batch->program ?? ($data['course'] ?? null),
                    'year_published' => $data['year_published'], 'school_year' => $batch->school_year,
                    'department' => $batch->department, 'research_handoff_id' => $batch->id,
                    'coordinator_id' => $batch->coordinator_id, 'submitted_by_dean_id' => $batch->dean_id,
                    'user_id' => $request->user()->id,
                    'status' => $batch->batch_stage === Research::STATUS_REJECTED ? Research::STATUS_REJECTED : Research::STATUS_DRAFT,
                    'file_path' => $path ?? $record->file_path,
                    'file_name' => $request->file('file')?->getClientOriginalName() ?? $record->file_name,
                ]);
                $record->save();
                $batch->update(['status' => ResearchHandoff::STATUS_RECEIVED,
                    'received_by_id' => $request->user()->id, 'received_at' => $batch->received_at ?? now()]);
            }, 3);
        } catch (\Throwable $error) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $error;
        }
        if ($path && $oldPath && $path !== $oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $parameters = ['batch' => $batch];
        if ($request->boolean('embedded')) $parameters['embedded'] = 1;

        return redirect()->route('admin.journal-batches.show', $parameters)->with('success', 'Journal saved in the batch. Add the remaining completed journals, then submit the whole batch to the Dean.');
    }

    public function transition(Request $request, ResearchHandoff $batch, string $action, JournalBatchWorkflow $workflow)
    {
        $workflow->transition($batch, $request->user(), $action, $request->input('reason'));

        $parameters = ['batch' => $batch];
        if ($request->boolean('embedded')) $parameters['embedded'] = 1;

        return redirect()->route('admin.journal-batches.show', $parameters)->with('success', [
            'submit' => 'Completed journal batch submitted to the Dean for review.',
            'forward' => 'Journal batch forwarded by the Dean to Admin for publishing.',
            'publish' => 'All journals in the batch published in the UBE Research Repository.',
        ][$action]);
    }
}
