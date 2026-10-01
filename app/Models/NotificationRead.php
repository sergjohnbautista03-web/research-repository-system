<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationRead extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'notification_key', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}
