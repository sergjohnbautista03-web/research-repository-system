<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $oldName = 'College of Engineering';
    private string $newName = 'College of Engineering and Architecture';

    public function up(): void
    {
        $this->renameDepartment($this->oldName, $this->newName);
    }

    public function down(): void
    {
        $this->renameDepartment($this->newName, $this->oldName);
    }

    private function renameDepartment(string $from, string $to): void
    {
        foreach ([
            'users' => 'department',
            'researches' => 'department',
            'department_access_keys' => 'department',
            'capture_attempt_logs' => 'viewer_department',
        ] as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->where($column, $from)
                ->update([$column => $to]);
        }
    }
};
