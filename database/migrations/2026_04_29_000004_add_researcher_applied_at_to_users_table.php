<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'researcher_applied_at')) {
                $table->timestamp('researcher_applied_at')->nullable()->after('researcher_rejected_at');
            }
        });

        if (Schema::hasColumn('users', 'researcher_applied_at')) {
            DB::table('users')
                ->where('role', 'researcher')
                ->where('is_approved', false)
                ->whereNull('researcher_applied_at')
                ->update(['researcher_applied_at' => DB::raw('updated_at')]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'researcher_applied_at')) {
                $table->dropColumn('researcher_applied_at');
            }
        });
    }
};
