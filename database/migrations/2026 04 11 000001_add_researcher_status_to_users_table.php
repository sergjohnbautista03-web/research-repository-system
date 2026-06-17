<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Legacy status column kept for compatibility.
            // The app now derives researcher status as Active or Graduated.
            $table->string('researcher_status')->nullable()->after('graduated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('researcher_status');
        });
    }
};
