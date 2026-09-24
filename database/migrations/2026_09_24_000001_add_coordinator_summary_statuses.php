<?php

use App\Models\Research;
use App\Models\ResearchHandoff;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('research_handoffs')) {
            Schema::table('research_handoffs', function (Blueprint $table) {
                if (! Schema::hasColumn('research_handoffs', 'received_by_id')) {
                    $table->foreignId('received_by_id')
                        ->nullable()
                        ->after('coordinator_id')
                        ->constrained('users')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('research_handoffs', 'received_at')) {
                    $table->timestamp('received_at')->nullable()->after('status');
                }
            });
        }

        if (Schema::hasTable('researches') && DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE researches MODIFY COLUMN status ENUM(
                'draft',
                'pending',
                'approved',
                'rejected',
                'archived'
            ) NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('researches')) {
            DB::table('researches')
                ->where('status', Research::STATUS_DRAFT)
                ->update(['status' => Research::STATUS_PENDING]);

            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE researches MODIFY COLUMN status ENUM(
                    'pending',
                    'approved',
                    'rejected',
                    'archived'
                ) NOT NULL DEFAULT 'pending'");
            }
        }

        if (Schema::hasTable('research_handoffs')) {
            DB::table('research_handoffs')
                ->where('status', ResearchHandoff::STATUS_RECEIVED)
                ->update(['status' => ResearchHandoff::STATUS_PENDING]);

            Schema::table('research_handoffs', function (Blueprint $table) {
                if (Schema::hasColumn('research_handoffs', 'received_by_id')) {
                    $table->dropConstrainedForeignId('received_by_id');
                }

                if (Schema::hasColumn('research_handoffs', 'received_at')) {
                    $table->dropColumn('received_at');
                }
            });
        }
    }
};
