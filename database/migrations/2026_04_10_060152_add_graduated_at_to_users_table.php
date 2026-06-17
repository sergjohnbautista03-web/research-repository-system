<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Timestamp kung kailan naging "graduated" ang researcher
            // NULL = hindi pa graduated / hindi researcher
            $table->timestamp('graduated_at')->nullable()->after('graduation_year');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('graduated_at');
        });
    }
};