<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_access_keys', function (Blueprint $table) {
            $table->id();
            $table->string('department');
            $table->string('semester');
            $table->string('school_year');
            $table->string('access_key');
            $table->timestamp('expires_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_access_keys');
    }
};
