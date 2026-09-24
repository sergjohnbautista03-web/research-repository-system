<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_research_coordinator')) {
                $table->boolean('is_research_coordinator')->default(false)->after('is_department_dean');
            }
        });

        if (! Schema::hasTable('research_handoffs')) {
            Schema::create('research_handoffs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dean_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('research_id')->nullable()->constrained('researches')->nullOnDelete();
                $table->string('department');
                $table->string('title', 500);
                $table->string('file_path');
                $table->string('file_name');
                $table->text('notes')->nullable();
                $table->string('status', 40)->default('pending');
                $table->timestamp('added_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('researches', function (Blueprint $table) {
            if (! Schema::hasColumn('researches', 'submitted_by_dean_id')) {
                $table->foreignId('submitted_by_dean_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('researches', 'coordinator_id')) {
                $table->foreignId('coordinator_id')
                    ->nullable()
                    ->after('submitted_by_dean_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('researches', 'research_handoff_id')) {
                $table->foreignId('research_handoff_id')
                    ->nullable()
                    ->after('coordinator_id')
                    ->constrained('research_handoffs')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('researches', function (Blueprint $table) {
            if (Schema::hasColumn('researches', 'research_handoff_id')) {
                $table->dropConstrainedForeignId('research_handoff_id');
            }

            if (Schema::hasColumn('researches', 'coordinator_id')) {
                $table->dropConstrainedForeignId('coordinator_id');
            }

            if (Schema::hasColumn('researches', 'submitted_by_dean_id')) {
                $table->dropConstrainedForeignId('submitted_by_dean_id');
            }
        });

        Schema::dropIfExists('research_handoffs');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_research_coordinator')) {
                $table->dropColumn('is_research_coordinator');
            }
        });
    }
};
