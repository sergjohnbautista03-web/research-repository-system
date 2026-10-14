<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_handoffs', function (Blueprint $table) {
            $table->boolean('is_batch')->default(false)->index();
            $table->string('batch_stage')->default('draft')->index();
            $table->text('batch_remarks')->nullable();
            foreach (['submitted', 'dean_approved', 'forwarded', 'published'] as $stage) {
                $table->timestamp('batch_'.$stage.'_at')->nullable();
            }
            $table->foreignId('batch_dean_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('batch_published_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('journal_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('research_handoffs')->cascadeOnDelete();
            $table->foreignId('source_research_id')->constrained('researches')->restrictOnDelete();
            $table->foreignId('journal_id')->nullable()->constrained('researches')->nullOnDelete();
            $table->timestamps();
            $table->unique(['batch_id', 'source_research_id']);
            // One source belongs to one journal in this batch; a journal may cover several papers.
            $table->index(['batch_id', 'journal_id']);
        });
        // Unstarted assignments can use batches without changing existing journal submissions.
        DB::table('research_handoffs')->whereNull('research_id')->whereNotNull('school_year')
            ->whereIn('status', ['pending', 'received'])->update(['is_batch' => true]);
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_batch_items');
        Schema::table('research_handoffs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_dean_approved_by');
            $table->dropConstrainedForeignId('batch_published_by');
            $table->dropIndex(['is_batch']);
            $table->dropIndex(['batch_stage']);
            $table->dropColumn(['is_batch', 'batch_stage', 'batch_remarks', 'batch_submitted_at',
                'batch_dean_approved_at', 'batch_forwarded_at', 'batch_published_at']);
        });
    }
};
