<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_semesters', function (Blueprint $table) {
            $table->id();
            $table->string('semester');
            $table->string('school_year', 9);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['semester', 'school_year']);
            $table->index(['school_year', 'semester']);
        });

        Schema::create('academic_semester_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_semester_id')->constrained('academic_semesters')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['academic_semester_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'current_academic_semester_id')) {
                $table->foreignId('current_academic_semester_id')
                    ->nullable()
                    ->after('department')
                    ->constrained('academic_semesters')
                    ->nullOnDelete();
            }
        });

        Schema::table('researches', function (Blueprint $table) {
            if (! Schema::hasColumn('researches', 'academic_semester_id')) {
                $table->foreignId('academic_semester_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('academic_semesters')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('researches', function (Blueprint $table) {
            if (Schema::hasColumn('researches', 'academic_semester_id')) {
                $table->dropConstrainedForeignId('academic_semester_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'current_academic_semester_id')) {
                $table->dropConstrainedForeignId('current_academic_semester_id');
            }
        });

        Schema::dropIfExists('academic_semester_user');
        Schema::dropIfExists('academic_semesters');
    }
};
