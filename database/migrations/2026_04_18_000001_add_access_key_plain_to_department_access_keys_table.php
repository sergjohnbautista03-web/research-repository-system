<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('department_access_keys', function (Blueprint $table) {
            $table->string('access_key_plain')->nullable()->after('access_key');
        });
    }

    public function down(): void
    {
        Schema::table('department_access_keys', function (Blueprint $table) {
            $table->dropColumn('access_key_plain');
        });
    }
};
