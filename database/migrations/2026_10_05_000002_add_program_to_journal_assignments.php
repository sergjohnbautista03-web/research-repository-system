<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_handoffs', function (Blueprint $table) {
            // Keep older assignments intact; their program cannot be inferred safely.
            $table->string('program')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('research_handoffs', function (Blueprint $table) {
            $table->dropIndex(['program']);
            $table->dropColumn('program');
        });
    }
};
