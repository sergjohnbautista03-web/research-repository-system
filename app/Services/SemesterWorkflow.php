<?php

namespace App\Services;

use App\Models\Semester;
use App\Models\SemesterEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SemesterWorkflow
{
    public function creationPlan(string $schoolYear, ?\Illuminate\Support\Collection $records = null): array
    {
        $records ??= Semester::where('school_year', $schoolYear)->get();
        $first = $records->firstWhere('semester', Semester::FIRST_SEMESTER);
        $second = $records->firstWhere('semester', Semester::SECOND_SEMESTER);
        if ($second) {
            return ['semester' => null, 'can_create' => false,
                'message' => 'Both semester slots for this academic year are already registered. Use a new academic year.'];
        }
        if (! $first) {
            return ['semester' => Semester::FIRST_SEMESTER, 'can_create' => true,
                'message' => 'A new academic year starts with 1st Semester.'];
        }
        if (! $first->hasExpired()) {
            $message = $first->end_date
                ? '2nd Semester can be created only after 1st Semester ends on ' . $first->end_date->format('M d, Y') . '.'
                : 'Set an end date for 1st Semester and wait until it finishes before creating 2nd Semester.';
            return ['semester' => Semester::SECOND_SEMESTER, 'can_create' => false, 'message' => $message];
        }
        return ['semester' => Semester::SECOND_SEMESTER, 'can_create' => true,
            'message' => '1st Semester has finished. The next term is 2nd Semester.'];
    }

    public function activate(Semester $semester, int $adminId): void
    {
        DB::transaction(function () use ($semester, $adminId) {
            // Lock in a consistent order, also coordinating with enrollment writes.
            $semesters = Semester::orderBy('id')->lockForUpdate()->get();
            $target = $semesters->firstWhere('id', $semester->id);
            abort_unless($target, 404);
            $wasActive = $target->is_active;
            if ($target->hasExpired()) {
                throw ValidationException::withMessages([
                    'semester' => 'Finished semesters cannot be activated. Create a new semester instead.',
                ]);
            }
            if ($target->semester === Semester::SECOND_SEMESTER) {
                $first = $semesters->first(fn ($item) => $item->school_year === $target->school_year
                    && $item->semester === Semester::FIRST_SEMESTER);
                if (! $first || ! $first->hasExpired()) {
                    throw ValidationException::withMessages([
                        'semester' => '2nd Semester cannot be activated until 1st Semester has finished.',
                    ]);
                }
            }

            $otherIds = $semesters->where('is_active', true)->where('id', '!=', $target->id)->pluck('id');
            Semester::whereIn('id', $otherIds)->update([
                'is_active' => false, 'closed_at' => now(), 'closed_by' => $adminId,
            ]);
            SemesterEnrollment::whereIn('semester_id', $otherIds)
                ->where('status', SemesterEnrollment::STATUS_ACTIVE)
                ->update(['status' => SemesterEnrollment::STATUS_ARCHIVED, 'updated_at' => now()]);

            $target->update(['is_active' => true, 'closed_at' => null, 'closed_by' => null]);
            // A restored term keeps its original enrollment records; new terms start empty.
            SemesterEnrollment::where('semester_id', $target->id)
                ->where('status', SemesterEnrollment::STATUS_ARCHIVED)
                ->update(['status' => SemesterEnrollment::STATUS_ACTIVE, 'updated_at' => now()]);

            if (! $wasActive) {
                app(AcademicPeriodNotifier::class)->notifyDeans($target, $adminId);
            }
        }, 3);
    }

    /** Call inside the transaction that creates or activates user enrollments. */
    public function lockCurrent(int $expectedId, string $errorBag = 'default'): Semester
    {
        $semester = Semester::whereKey($expectedId)->lockForUpdate()->first();
        if (! $semester || ! $semester->isOpen()) {
            throw ValidationException::withMessages([
                'semester' => 'The active semester changed or closed. Refresh the page and try again.',
            ])->errorBag($errorBag);
        }

        return $semester;
    }
}
