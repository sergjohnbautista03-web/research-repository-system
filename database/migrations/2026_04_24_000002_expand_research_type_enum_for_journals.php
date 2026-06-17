<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        DB::statement("UPDATE researches SET type = 'Applied Research' WHERE type IN (
            'Journal Article',
            'Review Article',
            'Case Report',
            'Short Communication'
        )");

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
            'Experimental Research'
        ) NOT NULL");
    }
};
