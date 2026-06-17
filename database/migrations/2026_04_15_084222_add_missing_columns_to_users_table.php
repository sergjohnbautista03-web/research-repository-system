<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Approval flag
            if (! Schema::hasColumn('users', 'is_approved')) {
                $table->boolean('is_approved')->default(false)->after('is_active');
            }

            // Activity tracking
            if (! Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('is_approved');
            }

            // Student / researcher academic info
            if (! Schema::hasColumn('users', 'year_level')) {
                $table->unsignedTinyInteger('year_level')->nullable()->after('student_id');
            }
            if (! Schema::hasColumn('users', 'course_duration')) {
                $table->unsignedTinyInteger('course_duration')->nullable()->after('year_level');
            }
            if (! Schema::hasColumn('users', 'graduation_year')) {
                $table->unsignedSmallInteger('graduation_year')->nullable()->after('course_duration');
            }
            if (! Schema::hasColumn('users', 'graduated_at')) {
                $table->timestamp('graduated_at')->nullable()->after('graduation_year');
            }

            // Legacy researcher status column. Active/Graduated is derived from graduation data.
            if (! Schema::hasColumn('users', 'researcher_status')) {
                $table->string('researcher_status')->nullable()->after('graduated_at');
            }

            // Role: set 'user' role as approved by default
            // (handled in seeder/factory or via the default above)
        });

        // Users with role='user' are auto-approved
        if (Schema::hasColumn('users', 'is_approved')) {
            \DB::table('users')->where('role', 'user')->update(['is_approved' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = collect([
                'is_approved',
                'last_seen_at',
                'year_level',
                'course_duration',
                'graduation_year',
                'graduated_at',
                'researcher_status',
            ])->filter(fn ($column) => Schema::hasColumn('users', $column))->values()->all();

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
