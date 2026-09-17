<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('researches', function (Blueprint $table) {
            if (! Schema::hasColumn('researches', 'citation_copy_count')) {
                $table->unsignedInteger('citation_copy_count')->default(0)->after('view_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('researches', function (Blueprint $table) {
            if (Schema::hasColumn('researches', 'citation_copy_count')) {
                $table->dropColumn('citation_copy_count');
            }
        });
    }
};
