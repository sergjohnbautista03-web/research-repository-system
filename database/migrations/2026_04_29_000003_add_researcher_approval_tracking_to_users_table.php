<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('researcher_approved_by')
                ->nullable()
                ->after('is_approved')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('researcher_approved_at')->nullable()->after('researcher_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('researcher_approved_by');
            $table->dropColumn('researcher_approved_at');
        });
    }
};
