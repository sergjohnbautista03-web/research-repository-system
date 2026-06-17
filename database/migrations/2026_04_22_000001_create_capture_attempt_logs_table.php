<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capture_attempt_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_id')->constrained('researches')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 60);
            $table->string('viewer_name')->nullable();
            $table->string('viewer_email')->nullable();
            $table->string('viewer_department')->nullable();
            $table->string('viewer_scope', 30)->default('standard');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['research_id', 'event_type']);
            $table->index(['user_id', 'created_at']);
            $table->index(['viewer_department', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capture_attempt_logs');
    }
};
