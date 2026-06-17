<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaptureAttemptLog extends Model
{
    public const EVENT_LOGIN_SUCCESS = 'login_success';
    public const EVENT_PROTECTED_VIEW_OPENED = 'protected_view_opened';

    public const SECURITY_EVENT_TYPES = [
        self::EVENT_PROTECTED_VIEW_OPENED,
        'printscreen',
        'print_blocked',
        'save_blocked',
        'copy_blocked',
        'source_view_blocked',
        'window_blur',
        'tab_hidden',
        'context_menu_blocked',
    ];

    protected $fillable = [
        'research_id',
        'user_id',
        'event_type',
        'viewer_name',
        'viewer_email',
        'viewer_department',
        'viewer_scope',
        'ip_address',
        'user_agent',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function research(): BelongsTo
    {
        return $this->belongsTo(Research::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(CaptureLogRead::class);
    }

    public static function securityEventTypes(): array
    {
        return self::SECURITY_EVENT_TYPES;
    }

    public static function adminFilterEventTypes(): array
    {
        return array_merge(self::SECURITY_EVENT_TYPES, [self::EVENT_LOGIN_SUCCESS]);
    }
}
