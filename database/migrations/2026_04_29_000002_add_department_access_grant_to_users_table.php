<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('department_access_department')->nullable()->after('department');
            $table->string('department_access_school_year')->nullable()->after('department_access_department');
            $table->string('department_access_semester')->nullable()->after('department_access_school_year');
            $table->timestamp('department_access_expires_at')->nullable()->after('department_access_semester');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'department_access_department',
                'department_access_school_year',
                'department_access_semester',
                'department_access_expires_at',
            ]);
        });
    }
};
