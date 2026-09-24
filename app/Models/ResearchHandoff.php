<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResearchHandoff extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_ADDED = 'added';

    protected $fillable = [
        'dean_id',
        'coordinator_id',
        'received_by_id',
        'research_id',
        'department',
        'title',
        'file_path',
        'file_name',
        'notes',
        'status',
        'received_at',
        'added_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'added_at' => 'datetime',
    ];

    public function dean()
    {
        return $this->belongsTo(User::class, 'dean_id');
    }

    public function coordinator()
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by_id');
    }

    public function research()
    {
        return $this->belongsTo(Research::class);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Received',
            self::STATUS_RECEIVED => 'Received',
            self::STATUS_ADDED => 'Preparing',
        ];
    }
}
