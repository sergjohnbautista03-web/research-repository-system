<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('researches', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('abstract');
            $table->string('author_name');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['thesis', 'dissertation', 'journal', 'conference', 'research', 'case-study']);
            $table->string('department');
            $table->string('program')->nullable();
            $table->integer('year_published');
            $table->string('keywords')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'archived'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->integer('view_count')->default(0);
            $table->integer('download_count')->default(0);
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('research_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('research_id')->constrained('researches')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'research_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_pins');
        Schema::dropIfExists('researches');
    }
};
