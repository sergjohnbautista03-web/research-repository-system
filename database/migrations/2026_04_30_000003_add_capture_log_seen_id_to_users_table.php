<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'capture_logs_seen_log_id')) {
                $table->unsignedBigInteger('capture_logs_seen_log_id')->default(0)->after('last_seen_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'capture_logs_seen_log_id')) {
                $table->dropColumn('capture_logs_seen_log_id');
            }
        });
    }
};
