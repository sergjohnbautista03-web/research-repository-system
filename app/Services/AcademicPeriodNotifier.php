<?php

namespace App\Services;

use App\Mail\AcademicPeriodActivatedMail;
use App\Models\Semester;
use App\Models\User;
use App\Notifications\AcademicPeriodActivated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AcademicPeriodNotifier
{
    /** Persist alerts in the activation transaction; send emails only after it commits. */
    public function notifyDeans(Semester $semester, int $adminId): void
    {
        $label = 'AY ' . $semester->school_year . ' — '
            . ($semester->semester === Semester::FIRST_SEMESTER ? '1st Semester' : '2nd Semester');
        $activatedAt = now(config('app.timezone', 'Asia/Manila'));
        $period = [
            'semester_id' => $semester->id,
            'school_year' => $semester->school_year,
            'semester' => $semester->semester,
            'label' => $label,
            'message' => $label . ' is now active. Create or import Student and Faculty accounts, or activate existing users for the current semester.',
            'start_date' => $semester->start_date?->format('F j, Y'),
            'end_date' => $semester->end_date?->format('F j, Y'),
            'activated_by' => $adminId,
            'activated_at' => $activatedAt->toIso8601String(),
            'activated_at_label' => $activatedAt->format('F j, Y · g:i A') . ' (' . $activatedAt->timezoneName . ')',
            'url' => route('admin.users'),
        ];
        $deans = User::query()->where('role', 'admin')->where('is_department_dean', true)
            ->where('is_active', true)->whereNotNull('department')->where('department', '!=', '')->get();

        foreach ($deans as $dean) {
            $dean->notifyNow(new AcademicPeriodActivated($period));
        }

        DB::afterCommit(function () use ($deans, $period) {
            $mailer = config('mail.default');
            // A logging fallback cannot deliver email. System alerts remain available.
            if (in_array($mailer, ['log', 'array'], true) && ! app()->environment('testing')) {
                return;
            }
            foreach ($deans as $dean) {
                if (! filter_var($dean->email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                try {
                    Mail::mailer($mailer === 'failover' ? 'smtp' : $mailer)->to($dean->email)
                        ->send(new AcademicPeriodActivatedMail($dean->name, $period));
                } catch (\Throwable $exception) {
                    // Failed delivery must not undo activation or prevent other deans receiving mail.
                    report($exception);
                }
            }
        });
    }
}
