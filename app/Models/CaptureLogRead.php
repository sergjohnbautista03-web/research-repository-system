<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaptureLogRead extends Model
{
    protected $fillable = [
        'user_id',
        'capture_attempt_log_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function captureAttemptLog(): BelongsTo
    {
        return $this->belongsTo(CaptureAttemptLog::class);
    }
}
