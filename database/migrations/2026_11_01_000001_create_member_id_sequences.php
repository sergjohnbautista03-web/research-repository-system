<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('users')->select('student_id')->whereNotNull('student_id')->groupBy('student_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Resolve duplicate users.student_id values before enabling automatic member IDs.');
        }
        // users.student_id is already VARCHAR; retain its leading zeros and legacy prefixes.
        Schema::table('users', fn (Blueprint $table) => $table->unique('student_id', 'users_student_id_unique'));
        Schema::create('member_id_sequences', function (Blueprint $table) {
            $table->string('member_type', 10)->primary();
            $table->unsignedBigInteger('last_value')->default(0);
        });
        Schema::create('member_identifiers', function (Blueprint $table) {
            $table->string('identifier')->primary();
            $table->string('member_type', 10)->nullable();
            $table->string('reservation_token', 64)->nullable()->unique();
            // Keep the history even after either account is permanently deleted.
            $table->unsignedBigInteger('reserved_by')->nullable();
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
        });
        $maxima = ['student' => 0, 'faculty' => 0];
        DB::table('users')->whereNotNull('student_id')->where('student_id', '!=', '')->orderBy('id')
            ->chunkById(500, function ($users) use (&$maxima) {
                $rows = [];
                foreach ($users as $user) {
                    $type = null;
                    if (preg_match('/\A[0-9]{8}\z/', $user->student_id)) {
                        $type = 'student';
                        $maxima[$type] = max($maxima[$type], (int) $user->student_id);
                    } elseif (preg_match('/\AFAC-([0-9]{1,6})\z/i', $user->student_id, $matches)) {
                        $type = 'faculty';
                        $maxima[$type] = max($maxima[$type], (int) $matches[1]);
                    }
                    $rows[] = ['identifier' => $user->student_id, 'member_type' => $type,
                        'assigned_user_id' => $user->id, 'assigned_at' => $user->created_at ?? now(),
                        'created_at' => now(), 'updated_at' => now()];
                }
                DB::table('member_identifiers')->insert($rows);
            });
        foreach ($maxima as $type => $value) {
            DB::table('member_id_sequences')->insert(['member_type' => $type, 'last_value' => $value]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('member_identifiers');
        Schema::dropIfExists('member_id_sequences');
        Schema::table('users', fn (Blueprint $table) => $table->dropUnique('users_student_id_unique'));
    }
};
