<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait JournalWorkflowSchema
{
    protected function createJournalWorkflowSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'email', 'password', 'role'] as $field) {
                $table->string($field)->nullable();
            }
            $table->string('department')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->date('researcher_end_date')->nullable();
            $table->boolean('is_department_dean')->default(false);
            $table->boolean('is_research_coordinator')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_approved')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('policy_accepted_at')->nullable();
            $table->string('policy_version')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('school_year');
            $table->string('semester');
            $table->boolean('is_active')->default(true);
            $table->date('end_date')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('researches', function (Blueprint $table) {
            $table->id();
            foreach (['title', 'submission_category', 'issn', 'type', 'author_name', 'department', 'course', 'program', 'keywords', 'file_path', 'file_name'] as $field) {
                $table->string($field)->nullable();
            }
            $table->json('authors')->nullable();
            $table->text('abstract')->nullable();
            $table->integer('year_published')->nullable();
            foreach (['user_id', 'coordinator_id', 'submitted_by_dean_id', 'research_handoff_id', 'semester_id', 'approved_by'] as $field) {
                $table->unsignedBigInteger($field)->nullable();
            }
            $table->string('status')->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('citation_copy_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('research_handoffs', function (Blueprint $table) {
            $table->id();
            foreach (['title', 'department', 'submission_category', 'file_path', 'file_name'] as $field) {
                $table->string($field)->nullable();
            }
            foreach (['dean_id', 'coordinator_id', 'research_id', 'received_by_id'] as $field) {
                $table->unsignedBigInteger($field)->nullable();
            }
            $table->integer('year_published')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('received_at')->nullable();
            $table->timestamp('added_at')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_04_000001_add_dean_journal_review_workflow.php'))->up();
        (require database_path('migrations/2026_10_05_000001_add_academic_year_journal_batches.php'))->up();
        (require database_path('migrations/2026_10_05_000002_add_program_to_journal_assignments.php'))->up();
        (require database_path('migrations/2026_10_02_000001_create_notifications_table.php'))->up();
        (require database_path('migrations/2026_10_02_000002_create_notification_reads_table.php'))->up();
        (require database_path('migrations/2026_09_29_000001_create_user_activity_logs_table.php'))->up();
        (require database_path('migrations/2026_10_06_000002_add_client_details_to_user_activity_logs.php'))->up();
    }
}
