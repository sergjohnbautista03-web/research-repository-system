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
        'submission_category',
        'year_published',
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
            self::STATUS_PENDING => 'Forwarded',
            self::STATUS_RECEIVED => 'Received by Research Coordinator',
            self::STATUS_ADDED => 'Preparing',
        ];
    }

    public static function workflowLabels(): array
    {
        return [
            'pending' => 'Forwarded',
            'received' => 'Received by Research Coordinator',
            'submitted' => 'Submitted to Admin for Review',
            'published' => 'Published',
            'returned' => 'Returned for Correction',
            'archived' => 'Archived',
        ];
    }

    public function workflowStage(): string
    {
        return match ($this->research?->status) {
            Research::STATUS_PENDING => 'submitted',
            Research::STATUS_APPROVED => 'published',
            Research::STATUS_REJECTED => 'returned',
            Research::STATUS_ARCHIVED => 'archived',
            default => $this->status === self::STATUS_PENDING ? 'pending' : 'received',
        };
    }

    public function workflowLabel(): string
    {
        return self::workflowLabels()[$this->workflowStage()];
    }

    public function scopeForWorkflowStage($query, string $stage)
    {
        $researchStatus = [
            'submitted' => Research::STATUS_PENDING, 'published' => Research::STATUS_APPROVED,
            'returned' => Research::STATUS_REJECTED, 'archived' => Research::STATUS_ARCHIVED,
        ];
        if (isset($researchStatus[$stage])) {
            return $query->whereHas('research', fn ($research) => $research->where('status', $researchStatus[$stage]));
        }
        if (in_array($stage, ['pending', 'received'], true)) {
            return $query->whereIn('status', $stage === 'pending' ? [self::STATUS_PENDING] : [self::STATUS_RECEIVED, self::STATUS_ADDED])
                ->whereDoesntHave('research', fn ($research) => $research->whereIn('status', array_values($researchStatus)));
        }
        return $query->whereRaw('1 = 0');
    }
}
