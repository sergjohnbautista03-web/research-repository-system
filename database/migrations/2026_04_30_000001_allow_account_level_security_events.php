<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capture_attempt_logs', function (Blueprint $table) {
            $table->dropForeign(['research_id']);
        });

        DB::statement('ALTER TABLE capture_attempt_logs MODIFY research_id BIGINT UNSIGNED NULL');

        Schema::table('capture_attempt_logs', function (Blueprint $table) {
            $table->foreign('research_id')
                ->references('id')
                ->on('researches')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('capture_attempt_logs')->whereNull('research_id')->delete();

        Schema::table('capture_attempt_logs', function (Blueprint $table) {
            $table->dropForeign(['research_id']);
        });

        DB::statement('ALTER TABLE capture_attempt_logs MODIFY research_id BIGINT UNSIGNED NOT NULL');

        Schema::table('capture_attempt_logs', function (Blueprint $table) {
            $table->foreign('research_id')
                ->references('id')
                ->on('researches')
                ->cascadeOnDelete();
        });
    }
};
