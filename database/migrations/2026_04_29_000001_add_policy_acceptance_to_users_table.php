<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('policy_accepted_at')->nullable()->after('last_seen_at');
            $table->string('policy_version', 32)->nullable()->after('policy_accepted_at');
            $table->string('policy_accepted_ip', 45)->nullable()->after('policy_version');
            $table->text('policy_accepted_user_agent')->nullable()->after('policy_accepted_ip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'policy_accepted_at',
                'policy_version',
                'policy_accepted_ip',
                'policy_accepted_user_agent',
            ]);
        });
    }
};
