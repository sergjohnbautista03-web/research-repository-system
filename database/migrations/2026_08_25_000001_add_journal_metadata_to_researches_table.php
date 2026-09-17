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
            if (! Schema::hasColumn('researches', 'authors')) {
                $table->json('authors')->nullable()->after('author_name');
            }

            if (! Schema::hasColumn('researches', 'issn')) {
                $table->string('issn', 20)->nullable()->after('submission_category');
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE researches MODIFY COLUMN author_name TEXT NOT NULL");
            DB::statement("ALTER TABLE researches MODIFY COLUMN type ENUM(
                'Thesis',
                'Feasibility Study',
                'Descriptive Research',
                'Correlational Research',
                'Quantitative Research',
                'Capstone 1',
                'Capstone 2',
                'Applied Research',
                'Qualitative Research',
                'Mixed Methods Research',
                'Action Research',
                'Experimental Research',
                'Journal Article',
                'Review Article',
                'Case Report',
                'Short Communication',
                'Quantitative',
                'Qualitative',
                'Descriptive',
                'Developmental'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("UPDATE researches SET type = 'Journal Article' WHERE type IN (
                'Quantitative',
                'Qualitative',
                'Descriptive',
                'Developmental'
            )");
            DB::statement("UPDATE researches SET author_name = LEFT(author_name, 255)");
            DB::statement("ALTER TABLE researches MODIFY COLUMN author_name VARCHAR(255) NOT NULL");
            DB::statement("ALTER TABLE researches MODIFY COLUMN type ENUM(
                'Thesis',
                'Feasibility Study',
                'Descriptive Research',
                'Correlational Research',
                'Quantitative Research',
                'Capstone 1',
                'Capstone 2',
                'Applied Research',
                'Qualitative Research',
                'Mixed Methods Research',
                'Action Research',
                'Experimental Research',
                'Journal Article',
                'Review Article',
                'Case Report',
                'Short Communication'
            ) NOT NULL");
        }

        Schema::table('researches', function (Blueprint $table) {
            if (Schema::hasColumn('researches', 'issn')) {
                $table->dropColumn('issn');
            }

            if (Schema::hasColumn('researches', 'authors')) {
                $table->dropColumn('authors');
            }
        });
    }
};
