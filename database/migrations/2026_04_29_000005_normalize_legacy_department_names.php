<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $renames = [
        'College of Computing' => 'College of Computer Studies',
        'Marine Department' => 'College of Maritime Studies',
    ];

    public function up(): void
    {
        $this->renameDepartments($this->renames);
    }

    public function down(): void
    {
        $this->renameDepartments(array_flip($this->renames));
    }

    private function renameDepartments(array $renames): void
    {
        $targets = [
            'users' => 'department',
            'researches' => 'department',
            'department_access_keys' => 'department',
            'capture_attempt_logs' => 'viewer_department',
        ];

        foreach ($targets as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            foreach ($renames as $from => $to) {
                DB::table($table)
                    ->where($column, $from)
                    ->update([$column => $to]);
            }
        }
    }
};
