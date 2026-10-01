<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('department');
            $table->string('user_name');
            $table->string('role', 16);
            $table->string('action', 40);
            $table->text('details');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['department', 'created_at']);
            $table->index(['department', 'action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};
