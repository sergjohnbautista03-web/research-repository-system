<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

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
            'Developmental',
            'Quantitative-Descriptive',
            'Descriptive-Developmental',
            'Experimental'
        ) NOT NULL");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE researches SET type = 'Quantitative' WHERE type = 'Quantitative-Descriptive'");
        DB::statement("UPDATE researches SET type = 'Descriptive' WHERE type = 'Descriptive-Developmental'");
        DB::statement("UPDATE researches SET type = 'Developmental' WHERE type = 'Experimental'");

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
};
