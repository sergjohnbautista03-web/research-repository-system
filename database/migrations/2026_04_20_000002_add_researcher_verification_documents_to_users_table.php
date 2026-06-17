<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'verification_documents')) {
                $table->json('verification_documents')->nullable()->after('middle_name');
            }

            if (! Schema::hasColumn('users', 'research_documents')) {
                $table->json('research_documents')->nullable()->after('verification_documents');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = collect([
                'verification_documents',
                'research_documents',
            ])->filter(fn ($column) => Schema::hasColumn('users', $column))->all();

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
