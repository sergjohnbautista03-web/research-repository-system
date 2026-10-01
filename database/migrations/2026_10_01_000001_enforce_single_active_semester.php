<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Retain the newest open term when upgrading older installations with multiple active terms.
        // Archive enrollment statuses without deleting any semester or enrollment records.
        DB::transaction(function () {
            $today = now(config('app.timezone', 'Asia/Manila'))->toDateString();
            $keepId = DB::table('semesters')->where('is_active', true)
                ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today))
                ->orderByDesc('school_year')->orderByDesc('semester')->orderByDesc('id')->value('id');
            $closeIds = DB::table('semesters')->where('is_active', true)
                ->when($keepId, fn ($query) => $query->where('id', '!=', $keepId))->pluck('id');
            DB::table('semesters')->whereIn('id', $closeIds)
                ->update(['is_active' => false, 'closed_at' => now(), 'updated_at' => now()]);
            DB::table('semester_enrollments')->whereIn('semester_id', $closeIds)->where('status', 'active')
                ->update(['status' => 'archived', 'updated_at' => now()]);
        });

        Schema::table('semesters', function (Blueprint $table) {
            // Multiple NULLs are allowed; the single value 1 can occur only once.
            $table->unsignedTinyInteger('active_slot')->nullable()
                ->virtualAs('CASE WHEN is_active = 1 THEN 1 ELSE NULL END');
            $table->unique('active_slot');
        });
    }

    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->dropUnique(['active_slot']);
            $table->dropColumn('active_slot');
        });
    }
};
