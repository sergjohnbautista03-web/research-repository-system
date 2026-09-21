<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('semesters')) {
            Schema::create('semesters', function (Blueprint $table) {
                $table->id();
                $table->string('school_year', 9);
                $table->string('semester', 3);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();

                $table->unique(['school_year', 'semester']);
                $table->index(['is_active', 'school_year', 'semester']);
            });
        }

        if (! Schema::hasTable('semester_enrollments')) {
            Schema::create('semester_enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
                $table->string('status', 20)->default('active');
                $table->timestamp('enrolled_at')->nullable();
                $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'semester_id']);
                $table->index(['semester_id', 'status']);
                $table->index(['user_id', 'status']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'current_semester_id')) {
                $table->foreignId('current_semester_id')
                    ->nullable()
                    ->after('department')
                    ->constrained('semesters')
                    ->nullOnDelete();
            }
        });

        Schema::table('researches', function (Blueprint $table) {
            if (! Schema::hasColumn('researches', 'semester_id')) {
                $table->foreignId('semester_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('semesters')
                    ->nullOnDelete();
            }
        });

        $this->migrateLegacyAcademicSemesters();
    }

    public function down(): void
    {
        Schema::table('researches', function (Blueprint $table) {
            if (Schema::hasColumn('researches', 'semester_id')) {
                $table->dropConstrainedForeignId('semester_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'current_semester_id')) {
                $table->dropConstrainedForeignId('current_semester_id');
            }
        });

        Schema::dropIfExists('semester_enrollments');
        Schema::dropIfExists('semesters');
    }

    private function migrateLegacyAcademicSemesters(): void
    {
        if (! Schema::hasTable('academic_semesters')) {
            return;
        }

        $legacyToNewIds = [];

        DB::table('academic_semesters')
            ->orderBy('id')
            ->get()
            ->each(function ($legacy) use (&$legacyToNewIds) {
                $semester = $this->normalizeLegacySemester($legacy->semester);

                if (! $semester || ! $legacy->school_year) {
                    return;
                }

                $existing = DB::table('semesters')
                    ->where('school_year', $legacy->school_year)
                    ->where('semester', $semester)
                    ->first();

                $semesterId = $existing?->id ?: DB::table('semesters')->insertGetId([
                    'school_year' => $legacy->school_year,
                    'semester' => $semester,
                    'start_date' => null,
                    'end_date' => null,
                    'is_active' => is_null($legacy->archived_at),
                    'created_by' => $legacy->created_by ?? null,
                    'closed_by' => $legacy->archived_by ?? null,
                    'closed_at' => $legacy->archived_at ?? null,
                    'created_at' => $legacy->created_at ?? now(),
                    'updated_at' => $legacy->updated_at ?? now(),
                ]);

                $legacyToNewIds[(int) $legacy->id] = (int) $semesterId;
            });

        if ($legacyToNewIds === []) {
            return;
        }

        if (Schema::hasColumn('users', 'current_academic_semester_id')) {
            foreach ($legacyToNewIds as $legacyId => $semesterId) {
                DB::table('users')
                    ->where('current_academic_semester_id', $legacyId)
                    ->whereNull('current_semester_id')
                    ->update(['current_semester_id' => $semesterId]);
            }
        }

        if (Schema::hasTable('academic_semester_user')) {
            DB::table('academic_semester_user')
                ->orderBy('id')
                ->get()
                ->each(function ($legacyEnrollment) use ($legacyToNewIds) {
                    $semesterId = $legacyToNewIds[(int) $legacyEnrollment->academic_semester_id] ?? null;

                    if (! $semesterId) {
                        return;
                    }

                    DB::table('semester_enrollments')->updateOrInsert(
                        [
                            'user_id' => $legacyEnrollment->user_id,
                            'semester_id' => $semesterId,
                        ],
                        [
                            'status' => 'active',
                            'enrolled_by' => $legacyEnrollment->assigned_by ?? null,
                            'enrolled_at' => $legacyEnrollment->assigned_at ?? $legacyEnrollment->created_at ?? now(),
                            'created_at' => $legacyEnrollment->created_at ?? now(),
                            'updated_at' => $legacyEnrollment->updated_at ?? now(),
                        ]
                    );
                });
        }

        if (Schema::hasColumn('researches', 'academic_semester_id')) {
            foreach ($legacyToNewIds as $legacyId => $semesterId) {
                DB::table('researches')
                    ->where('academic_semester_id', $legacyId)
                    ->whereNull('semester_id')
                    ->update(['semester_id' => $semesterId]);
            }
        }
    }

    private function normalizeLegacySemester(?string $semester): ?string
    {
        return match ($semester) {
            'First Semester', '1st', '1st Sem' => '1st',
            'Second Semester', '2nd', '2nd Sem' => '2nd',
            default => null,
        };
    }
};
