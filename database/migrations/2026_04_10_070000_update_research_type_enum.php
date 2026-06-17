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
            'Experimental Research'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE researches MODIFY COLUMN type ENUM(
            'thesis',
            'dissertation',
            'journal',
            'conference',
            'research',
            'case-study'
        ) NOT NULL");
    }
};