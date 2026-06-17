<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'researcher_rejection_reason')) {
                $table->text('researcher_rejection_reason')->nullable()->after('researcher_status');
            }

            if (! Schema::hasColumn('users', 'researcher_rejected_at')) {
                $table->timestamp('researcher_rejected_at')->nullable()->after('researcher_rejection_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'researcher_rejected_at')) {
                $table->dropColumn('researcher_rejected_at');
            }

            if (Schema::hasColumn('users', 'researcher_rejection_reason')) {
                $table->dropColumn('researcher_rejection_reason');
            }
        });
    }
};
