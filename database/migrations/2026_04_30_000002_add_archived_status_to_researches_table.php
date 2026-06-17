<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE researches MODIFY COLUMN status ENUM(
            'pending',
            'approved',
            'rejected',
            'archived'
        ) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("UPDATE researches SET status = 'approved' WHERE status = 'archived'");

        DB::statement("ALTER TABLE researches MODIFY COLUMN status ENUM(
            'pending',
            'approved',
            'rejected'
        ) NOT NULL DEFAULT 'pending'");
    }
};
