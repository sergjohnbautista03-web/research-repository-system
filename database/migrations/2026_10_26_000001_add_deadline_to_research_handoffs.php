<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_handoffs', function (Blueprint $table) {
            $table->date('deadline')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('research_handoffs', function (Blueprint $table) {
            $table->dropColumn('deadline');
        });
    }
};
