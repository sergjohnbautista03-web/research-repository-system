<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'student_approved_by')) {
                $table->foreignId('student_approved_by')
                    ->nullable()
                    ->after('is_approved')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'student_approved_at')) {
                $table->timestamp('student_approved_at')->nullable()->after('student_approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'student_approved_by')) {
                $table->dropConstrainedForeignId('student_approved_by');
            }

            if (Schema::hasColumn('users', 'student_approved_at')) {
                $table->dropColumn('student_approved_at');
            }
        });
    }
};
