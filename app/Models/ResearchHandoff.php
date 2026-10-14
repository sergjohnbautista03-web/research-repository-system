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
        'program',
        'school_year',
        'title',
        'submission_category',
        'year_published',
        'file_path',
        'file_name',
        'notes',
        'status',
        'received_at',
        'added_at',
        'is_batch', 'batch_stage', 'batch_remarks', 'batch_submitted_at', 'batch_dean_approved_at',
        'batch_forwarded_at', 'batch_published_at', 'batch_dean_approved_by', 'batch_published_by',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'added_at' => 'datetime',
        'is_batch' => 'boolean',
        'batch_submitted_at' => 'datetime',
        'batch_dean_approved_at' => 'datetime',
        'batch_forwarded_at' => 'datetime',
        'batch_published_at' => 'datetime',
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
            self::STATUS_PENDING => 'Task Assigned',
            self::STATUS_RECEIVED => 'Received by Research Coordinator',
            self::STATUS_ADDED => 'Preparing',
        ];
    }

    public function batchItems()
    {
        return $this->hasMany(JournalBatchItem::class, 'batch_id');
    }

    public function journals()
    {
        return $this->hasMany(Research::class, 'research_handoff_id');
    }

    public function batchLabel(): string
    {
        return [Research::STATUS_DRAFT => 'Preparing Journals', Research::STATUS_DEAN_REVIEW => 'Batch Submitted to Dean',
            Research::STATUS_REJECTED => 'Preparing Journals', Research::STATUS_DEAN_APPROVED => 'Batch Ready to Forward',
            Research::STATUS_PENDING => 'Batch Forwarded to Admin', Research::STATUS_APPROVED => 'Batch Published'][$this->batch_stage] ?? 'Preparing Journals';
    }

    public static function workflowLabels(): array
    {
        return [
            'pending' => 'Assigned',
            'received' => 'Received by Research Coordinator',
            'preparing' => 'Preparing Journals',
            'submitted' => 'Submitted to Dean',
            'dean_approved' => 'Approved by Dean',
            'forwarded' => 'Forwarded to Admin',
            'published' => 'Published',
            'returned' => 'Returned for Correction',
            'archived' => 'Archived',
        ];
    }

    public function workflowStage(): string
    {
        if ($this->is_batch) {
            return [Research::STATUS_DEAN_REVIEW => 'submitted', Research::STATUS_DEAN_APPROVED => 'dean_approved',
                Research::STATUS_PENDING => 'forwarded', Research::STATUS_APPROVED => 'published',
                Research::STATUS_REJECTED => 'returned'][$this->batch_stage]
                ?? ($this->status === self::STATUS_PENDING ? 'pending' : 'received');
        }
        return match ($this->research?->status) {
            Research::STATUS_DEAN_REVIEW => 'submitted',
            Research::STATUS_DEAN_APPROVED => 'dean_approved',
            Research::STATUS_PENDING => 'forwarded',
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

    public function scopeAwaitingJournalPreparation($query)
    {
        return $query->where(fn ($tasks) => $tasks
            ->where(fn ($batch) => $batch->where('is_batch', true)
                ->whereIn('batch_stage', [Research::STATUS_DRAFT, Research::STATUS_REJECTED]))
            ->orWhere(fn ($legacy) => $legacy->where('is_batch', false)
                ->where(fn ($journal) => $journal->whereDoesntHave('research')
                    ->orWhereHas('research', fn ($research) => $research
                        ->whereIn('status', [Research::STATUS_DRAFT, Research::STATUS_REJECTED])))));
    }

    public function scopeForWorkflowStage($query, string $stage)
    {
        if ($stage === 'preparing') {
            return $query->where(fn ($preparing) => $preparing
                ->where(fn ($batch) => $batch->where('is_batch', true)
                    ->whereIn('batch_stage', [Research::STATUS_DRAFT, Research::STATUS_REJECTED])
                    ->whereIn('status', [self::STATUS_RECEIVED, self::STATUS_ADDED]))
                ->orWhere(fn ($legacy) => $legacy->where('is_batch', false)
                    ->whereHas('research', fn ($research) => $research->whereIn('status', [Research::STATUS_DRAFT, Research::STATUS_REJECTED]))));
        }
        $researchStatus = [
            'submitted' => Research::STATUS_DEAN_REVIEW, 'dean_approved' => Research::STATUS_DEAN_APPROVED,
            'forwarded' => Research::STATUS_PENDING, 'published' => Research::STATUS_APPROVED,
            'returned' => Research::STATUS_REJECTED, 'archived' => Research::STATUS_ARCHIVED,
        ];
        if (isset($researchStatus[$stage])) {
            return $query->where(fn ($q) => $q->where(fn ($batch) => $batch->where('is_batch', true)->where('batch_stage', $researchStatus[$stage]))
                ->orWhere(fn ($legacy) => $legacy->where('is_batch', false)->whereHas('research', fn ($research) => $research->where('status', $researchStatus[$stage]))));
        }
        if (in_array($stage, ['pending', 'received'], true)) {
            return $query->whereIn('status', $stage === 'pending' ? [self::STATUS_PENDING] : [self::STATUS_RECEIVED, self::STATUS_ADDED])
                ->where(fn ($q) => $q->where('is_batch', false)->orWhere('batch_stage', Research::STATUS_DRAFT))
                ->whereDoesntHave('research', fn ($research) => $research->whereIn('status', array_values($researchStatus)));
        }
        return $query->whereRaw('1 = 0');
    }
}
