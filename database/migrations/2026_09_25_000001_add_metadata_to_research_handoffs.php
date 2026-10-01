<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_handoffs', function (Blueprint $table) {
            $table->string('submission_category')->nullable();
            $table->unsignedSmallInteger('year_published')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('research_handoffs', function (Blueprint $table) {
            $table->dropColumn(['submission_category', 'year_published']);
        });
    }
};
