<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActivityLog extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['created_at' => 'datetime'];

    public const ACTIONS = [
        'login' => 'Login',
        'viewed_research' => 'Viewed Research',
        'saved_research' => 'Saved Research',
        'unsaved_research' => 'Unsaved Research',
        'copied_citation' => 'Copied Citation',
        'profile_updated' => 'Profile Updated',
        'password_changed' => 'Password Changed',
        'research_access_attempt' => 'Research Access Attempt',
    ];

    public const SYSTEM_ACTIONS = [
        'login' => 'Login',
        'logout' => 'Logout',
        'report_generated' => 'Generated Report',
        'report_exported' => 'Exported Report',
        'account_activated' => 'User Account Activated',
        'account_deactivated' => 'User Account Deactivated',
        'research_forwarded' => 'Research Forwarded',
        'account_created' => 'Account Created',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
