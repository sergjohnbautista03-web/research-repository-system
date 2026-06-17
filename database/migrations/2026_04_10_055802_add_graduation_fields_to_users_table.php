<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('year_level')->nullable()->after('student_id');
            $table->unsignedTinyInteger('course_duration')->nullable()->after('year_level');
            $table->unsignedSmallInteger('graduation_year')->nullable()->after('course_duration');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['year_level', 'course_duration', 'graduation_year']);
        });
    }
};