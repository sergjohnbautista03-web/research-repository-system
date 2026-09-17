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
            $table->index(['status', 'year_published', 'created_at'], 'researches_status_year_created_idx');
            $table->index(['status', 'department', 'year_published'], 'researches_status_dept_year_idx');
            $table->index(['status', 'department', 'course', 'year_published'], 'researches_status_dept_course_year_idx');
            $table->index(['status', 'type', 'year_published'], 'researches_status_type_year_idx');
            $table->index(['status', 'view_count'], 'researches_status_view_count_idx');
            $table->index(['academic_semester_id', 'status', 'created_at'], 'researches_semester_status_created_idx');
        });

        if ($this->isMysql() && ! $this->indexExists('researches', 'researches_search_fulltext')) {
            DB::statement(
                'ALTER TABLE researches ADD FULLTEXT INDEX researches_search_fulltext (title, abstract, author_name, keywords)'
            );
        }
    }

    public function down(): void
    {
        if ($this->isMysql() && $this->indexExists('researches', 'researches_search_fulltext')) {
            DB::statement('ALTER TABLE researches DROP INDEX researches_search_fulltext');
        }

        Schema::table('researches', function (Blueprint $table) {
            $table->dropIndex('researches_semester_status_created_idx');
            $table->dropIndex('researches_status_view_count_idx');
            $table->dropIndex('researches_status_type_year_idx');
            $table->dropIndex('researches_status_dept_course_year_idx');
            $table->dropIndex('researches_status_dept_year_idx');
            $table->dropIndex('researches_status_year_created_idx');
        });
    }

    private function isMysql(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::connection()->getDatabaseName();

        return (bool) DB::selectOne(
            'SELECT INDEX_NAME FROM information_schema.statistics WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$database, $table, $index]
        );
    }
};
