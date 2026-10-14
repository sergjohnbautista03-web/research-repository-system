<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_handoffs', function (Blueprint $table) {
            $table->string('school_year', 9)->nullable()->index();
            $table->string('file_path')->nullable()->change();
            $table->string('file_name')->nullable()->change();
        });
        Schema::table('researches', function (Blueprint $table) {
            $table->string('school_year', 9)->nullable()->index();
            $table->foreignId('dean_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_to_dean_at')->nullable();
            $table->timestamp('dean_approved_at')->nullable();
            $table->timestamp('forwarded_to_admin_at')->nullable();
        });
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE researches MODIFY COLUMN status ENUM('draft','dean_review','dean_approved','pending','approved','rejected','archived') NOT NULL DEFAULT 'pending'");
        }

        // Existing unpublished coordinator submissions must pass Dean review first.
        DB::table('researches')->whereNotNull('coordinator_id')->where('status', 'pending')
            ->update(['status' => 'dean_review', 'submitted_to_dean_at' => now()]);
        DB::table('research_handoffs')->whereNotNull('year_published')->orderBy('id')->each(function ($task) {
            $start = (int) $task->year_published;
            if ($start >= 2000) {
                DB::table('research_handoffs')->where('id', $task->id)->update(['school_year' => $start.'-'.($start + 1)]);
            }
        });
        DB::table('researches')->orderBy('id')->each(function ($research) {
            $year = $research->semester_id ? DB::table('semesters')->where('id', $research->semester_id)->value('school_year') : null;
            $year ??= $research->research_handoff_id
                ? DB::table('research_handoffs')->where('id', $research->research_handoff_id)->value('school_year') : null;
            if ($year) {
                DB::table('researches')->where('id', $research->id)->update(['school_year' => $year]);
                if ($research->research_handoff_id) {
                    DB::table('research_handoffs')->where('id', $research->research_handoff_id)->update(['school_year' => $year]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::table('researches')->whereIn('status', ['dean_review', 'dean_approved'])->update(['status' => 'draft']);
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE researches MODIFY COLUMN status ENUM('draft','pending','approved','rejected','archived') NOT NULL DEFAULT 'pending'");
        }
        Schema::table('researches', function (Blueprint $table) {
            $table->dropIndex(['school_year']);
            $table->dropConstrainedForeignId('dean_approved_by');
            $table->dropColumn(['school_year', 'submitted_to_dean_at', 'dean_approved_at', 'forwarded_to_admin_at']);
        });
        Schema::table('research_handoffs', function (Blueprint $table) {
            $table->dropIndex(['school_year']);
            $table->dropColumn('school_year');
        });
    }
};
