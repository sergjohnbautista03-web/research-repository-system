<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('researches', function (Blueprint $table) {
            $table->string('submission_category')->default('research')->after('user_id');
        });

        DB::table('researches')
            ->whereIn('type', ['journal', 'Journal Article', 'Review Article', 'Case Report', 'Short Communication'])
            ->update(['submission_category' => 'journal']);
    }

    public function down(): void
    {
        Schema::table('researches', function (Blueprint $table) {
            $table->dropColumn('submission_category');
        });
    }
};
