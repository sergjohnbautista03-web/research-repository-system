<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The existing published 2023 collection belongs to academic year 2022-2023.
        DB::table('researches')->where('status', 'approved')->where('year_published', 2023)
            ->whereNull('deleted_at')->whereNull('semester_id')->whereNull('research_handoff_id')
            ->where(fn ($query) => $query->whereNull('school_year')->orWhere('school_year', ''))
            ->update(['school_year' => '2022-2023']);
    }

    public function down(): void
    {
        // Retain the corrected academic-year metadata when rolling back application changes.
    }
};
