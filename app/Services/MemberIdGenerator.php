<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberIdGenerator
{
    // Call inside the account-creation transaction to serialize duplicate-ID checks.
    public function forManualCreation(string $type, string $number): string
    {
        $identifier = $type === 'student' ? str_pad($number, 8, '0', STR_PAD_LEFT) : 'FAC-'.$number;
        DB::table('member_id_sequences')->where('member_type', $type)->lockForUpdate()->first();
        $previous = DB::table('member_identifiers')->where('identifier', $identifier)->whereNotNull('assigned_at')->exists();

        if ($previous || DB::table('users')->where('student_id', $identifier)->exists()) {
            throw ValidationException::withMessages(['student_id' => 'This ID is already registered or was previously assigned. Choose another number.'])
                ->errorBag('addUser');
        }

        return $identifier;
    }

    public function assertAssignable(string $identifier, ?int $userId = null): void
    {
        $previous = DB::table('member_identifiers')->where('identifier', $identifier)->whereNotNull('assigned_at')->first();
        if ($previous && (int) $previous->assigned_user_id !== $userId) {
            throw ValidationException::withMessages(['student_id' => 'This ID has already been assigned and cannot be reused.']);
        }
    }

    public function rememberAssigned(User $user): void
    {
        $identifier = (string) $user->student_id;
        $type = null;
        $number = 0;
        if (preg_match('/\A[0-9]{8}\z/', $identifier)) {
            $type = 'student';
            $number = (int) $identifier;
        } elseif (preg_match('/\AFAC-([0-9]{1,6})\z/i', $identifier, $matches)) {
            $type = 'faculty';
            $number = (int) $matches[1];
        }
        if ($type) {
            DB::table('member_id_sequences')->where('member_type', $type)->where('last_value', '<', $number)
                ->update(['last_value' => $number]);
        }
        DB::table('member_identifiers')->insertOrIgnore([
            'identifier' => $identifier, 'member_type' => $type,
            'assigned_user_id' => $user->id, 'assigned_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('member_identifiers')->where('identifier', $identifier)->whereNull('assigned_at')->update([
            'assigned_user_id' => $user->id, 'assigned_at' => now(), 'updated_at' => now(),
        ]);
    }
}
