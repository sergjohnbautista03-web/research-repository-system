<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_download_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_id')->constrained('researches')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('downloader_name')->nullable();
            $table->string('downloader_email')->nullable();
            $table->string('downloader_role', 40)->nullable();
            $table->string('downloader_department')->nullable();
            $table->string('download_scope', 30)->default('admin');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['research_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['downloader_department', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_download_logs');
    }
};
