<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('researchers:deactivate-graduated', function () {
    $count = 0;

    User::query()
        ->where('role', 'researcher')
        ->where(function ($query) {
            $query->where('is_active', true)
                ->orWhereNull('graduated_at');
        })
        ->where(function ($query) {
            $query->whereNotNull('graduated_at')
                ->orWhereNotNull('researcher_end_date')
                ->orWhereNotNull('graduation_year');
        })
        ->chunkById(100, function ($users) use (&$count) {
            foreach ($users as $user) {
                $wasActive = $user->is_active;
                $hadGraduatedAt = ! is_null($user->graduated_at);

                if ($user->deactivateIfGraduated() && ($wasActive || ! $hadGraduatedAt)) {
                    $count++;
                }
            }
        });

    $this->info("Deactivated {$count} graduated researcher account(s).");
})->purpose('Deactivate researcher accounts that have reached graduation.');

Schedule::command('researchers:deactivate-graduated')->daily();
