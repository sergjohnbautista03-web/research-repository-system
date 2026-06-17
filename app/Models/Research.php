<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Research extends Model
{
    use HasFactory, SoftDeletes;

    public const SUBMISSION_CATEGORY_RESEARCH = 'research';
    public const SUBMISSION_CATEGORY_JOURNAL = 'journal';

    public const TYPE_OPTIONS = [
        self::SUBMISSION_CATEGORY_RESEARCH => [
            'Thesis',
            'Feasibility Study',
            'Descriptive Research',
            'Correlational Research',
            'Quantitative Research',
            'Capstone 1',
            'Capstone 2',
            'Applied Research',
            'Qualitative Research',
            'Mixed Methods Research',
            'Action Research',
            'Experimental Research',
        ],
        self::SUBMISSION_CATEGORY_JOURNAL => [
            'Journal Article',
        ],
    ];

    protected $table = 'researches';

    protected $fillable = [
        'title', 'abstract', 'author_name', 'user_id', 'submission_category', 'type',
        'department', 'course', 'program', 'year_published', 'keywords',
        'file_path', 'file_name', 'status', 'rejection_reason',
        'view_count', 'download_count', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'year_published' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function pinnedByUsers()
    {
        return $this->belongsToMany(User::class, 'research_pins')->withTimestamps();
    }

    public function downloadLogs()
    {
        return $this->hasMany(ResearchDownloadLog::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('abstract', 'like', "%{$term}%")
              ->orWhere('author_name', 'like', "%{$term}%")
              ->orWhere('keywords', 'like', "%{$term}%");
        });
    }

    public function getTypeLabel(): string
    {
        if (is_string($this->type) && $this->type !== '') {
            return str_contains($this->type, '-') ? ucwords(str_replace('-', ' ', $this->type)) : $this->type;
        }

        return 'Uncategorized';
    }

    public function getSubmissionCategoryLabel(): string
    {
        return match($this->submission_category ?: self::inferCategoryFromType($this->type)) {
            self::SUBMISSION_CATEGORY_JOURNAL => 'Journal',
            default => 'Research',
        };
    }

    public static function submissionCategories(): array
    {
        return [
            self::SUBMISSION_CATEGORY_JOURNAL => 'Journal',
        ];
    }

    public static function typesForCategory(?string $category): array
    {
        $category ??= self::SUBMISSION_CATEGORY_JOURNAL;

        return self::TYPE_OPTIONS[$category] ?? self::TYPE_OPTIONS[self::SUBMISSION_CATEGORY_JOURNAL];
    }

    public static function allTypes(): array
    {
        return array_merge(...array_values(self::TYPE_OPTIONS));
    }

    public static function inferCategoryFromType(?string $type): string
    {
        if ($type && in_array($type, self::TYPE_OPTIONS[self::SUBMISSION_CATEGORY_JOURNAL], true)) {
            return self::SUBMISSION_CATEGORY_JOURNAL;
        }

        return self::SUBMISSION_CATEGORY_RESEARCH;
    }
}
