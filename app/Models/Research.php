<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;


class Research extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_DEAN_REVIEW = 'dean_review';
    public const STATUS_DEAN_APPROVED = 'dean_approved';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ARCHIVED = 'archived';

    public const SUBMISSION_CATEGORY_RESEARCH = 'research';
    public const SUBMISSION_CATEGORY_JOURNAL = 'journal';
    public const SUBMISSION_CATEGORY_FACULTY_JOURNAL = 'faculty_research_journal';
    public const SUBMISSION_CATEGORY_STUDENT_JOURNAL = 'student_research_journal';

    public const JOURNAL_TYPE_OPTIONS = [
        'Quantitative',
        'Qualitative',
        'Descriptive',
        'Developmental',
        'Quantitative-Descriptive',
        'Descriptive-Developmental',
        'Experimental',
    ];

    public const LEGACY_JOURNAL_TYPE_OPTIONS = [
        'Journal Article',
        'Review Article',
        'Case Report',
        'Short Communication',
    ];

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
        self::SUBMISSION_CATEGORY_JOURNAL => self::JOURNAL_TYPE_OPTIONS,
        self::SUBMISSION_CATEGORY_FACULTY_JOURNAL => self::JOURNAL_TYPE_OPTIONS,
        self::SUBMISSION_CATEGORY_STUDENT_JOURNAL => self::JOURNAL_TYPE_OPTIONS,
    ];

    protected $table = 'researches';

    protected $appends = ['research_id'];

    protected static function booted(): void
    {
        static::updating(function (Research $research) {
            if ($research->isDirty($research->getKeyName())) {
                throw new \LogicException('The permanent research number cannot be changed.');
            }
        });
    }

    public function getResearchIdAttribute(): ?string
    {
        // The database allocates unique, increasing primary keys, including across deletions.
        // Deriving the public ID preserves existing records and never renumbers a paper.
        return $this->getKey() === null ? null : 'RES-'.str_pad((string) $this->getKey(), 4, '0', STR_PAD_LEFT);
    }

    public function setResearchIdAttribute($value): void
    {
        throw new \LogicException('Research IDs are generated automatically and cannot be edited.');
    }

    public static function keyForResearchId(?string $value): ?string
    {
        if (! preg_match('/^RES-(\d+)$/i', trim((string) $value), $matches)) {
            return null;
        }

        $key = ltrim($matches[1], '0');

        return $key === '' ? null : $key;
    }

    public function scopeOrWhereResearchId($query, string $term)
    {
        if ($key = static::keyForResearchId($term)) {
            $query->orWhere($this->qualifyColumn($this->getKeyName()), $key);
        }

        return $query;
    }

    protected $fillable = [
        'title', 'abstract', 'author_name', 'authors', 'user_id', 'submitted_by_dean_id', 'coordinator_id',
        'research_handoff_id', 'submission_category', 'issn', 'type',
        'semester_id', 'academic_semester_id',
        'school_year', 'dean_approved_by', 'submitted_to_dean_at', 'dean_approved_at', 'forwarded_to_admin_at',
        'department', 'course', 'program', 'year_published', 'keywords',
        'file_path', 'file_name', 'status', 'rejection_reason',
        'view_count', 'citation_copy_count', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'submitted_to_dean_at' => 'datetime',
        'dean_approved_at' => 'datetime',
        'forwarded_to_admin_at' => 'datetime',
        'year_published' => 'integer',
        'authors' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function academicSemester()
    {
        return $this->semester();
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function submittedByDean()
    {
        return $this->belongsTo(User::class, 'submitted_by_dean_id');
    }

    public function coordinator()
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function deanApprovedBy()
    {
        return $this->belongsTo(User::class, 'dean_approved_by');
    }

    public function scopeForAdmin($query)
    {
        return $query->where(function ($inner) {
            $inner->where(fn ($direct) => $direct->whereNull('coordinator_id')->whereNull('research_handoff_id'))
                ->orWhereIn('status', [self::STATUS_APPROVED, self::STATUS_ARCHIVED])
                ->orWhere(fn ($forwarded) => $forwarded->where('status', self::STATUS_PENDING)
                    ->whereNotNull('forwarded_to_admin_at'));
        });
    }

    public static function maxPublicationYear(): int
    {
        return max(2026, now('Asia/Manila')->year + 1);
    }

    public function academicYearLabel(): string
    {
        return $this->school_year ?: ($this->semester?->school_year ?? 'Not set');
    }

    public function handoff()
    {
        return $this->belongsTo(ResearchHandoff::class, 'research_handoff_id');
    }

    public function pinnedByUsers()
    {
        return $this->belongsToMany(User::class, 'research_pins')->withTimestamps();
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSearch($query, $term)
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        if ($key = static::keyForResearchId($term)) {
            return $query->whereKey($key);
        }

        if (static::canUseFullTextSearch()) {
            $booleanTerm = collect(preg_split('/\s+/', $term) ?: [])
                ->filter()
                ->map(function ($word) {
                    $cleanWord = preg_replace('/[^\pL\pN_]+/u', '', $word) ?? '';

                    return $cleanWord !== '' ? '+' . $cleanWord . '*' : null;
                })
                ->filter()
                ->implode(' ');

            if ($booleanTerm !== '') {
                return $query
                    ->whereRaw(
                        'MATCH(title, abstract, author_name, keywords) AGAINST (? IN BOOLEAN MODE)',
                        [$booleanTerm]
                    )
                    ->orderByRaw(
                        'MATCH(title, abstract, author_name, keywords) AGAINST (? IN BOOLEAN MODE) DESC',
                        [$booleanTerm]
                    );
            }
        }

        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('abstract', 'like', "%{$term}%")
              ->orWhere('author_name', 'like', "%{$term}%")
              ->orWhere('keywords', 'like', "%{$term}%");
        });
    }

    private static function canUseFullTextSearch(): bool
    {
        static $hasFullTextIndex = null;

        if ($hasFullTextIndex !== null) {
            return $hasFullTextIndex;
        }

        try {
            $connection = DB::connection();

            if ($connection->getDriverName() !== 'mysql') {
                return $hasFullTextIndex = false;
            }

            $database = $connection->getDatabaseName();

            $index = $connection->selectOne(
                'SELECT INDEX_NAME FROM information_schema.statistics WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
                [$database, 'researches', 'researches_search_fulltext']
            );

            return $hasFullTextIndex = (bool) $index;
        } catch (\Throwable) {
            return $hasFullTextIndex = false;
        }
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
            self::SUBMISSION_CATEGORY_FACULTY_JOURNAL => 'Faculty Research Journal',
            self::SUBMISSION_CATEGORY_STUDENT_JOURNAL => 'Student Research Journal',
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

    public static function adminSubmissionCategories(): array
    {
        $categories = config('research.admin_submission_categories');

        return is_array($categories) && $categories !== []
            ? $categories
            : [
                self::SUBMISSION_CATEGORY_FACULTY_JOURNAL => 'Faculty Research Journal',
                self::SUBMISSION_CATEGORY_STUDENT_JOURNAL => 'Student Research Journal',
            ];
    }

    public static function departmentOptions(): array
    {
        $departments = config('research.departments');

        return is_array($departments) ? $departments : [];
    }

    public static function programsByDepartment(): array
    {
        $programs = config('research.programs');

        return is_array($programs) ? $programs : [];
    }

    public static function journalTypeOptions(): array
    {
        $types = config('research.journal_types');

        return is_array($types) && $types !== [] ? $types : self::JOURNAL_TYPE_OPTIONS;
    }

    public static function issnForSubmissionCategory(?string $category): ?string
    {
        $issns = config('research.issn');

        return is_array($issns) ? ($issns[$category] ?? null) : null;
    }

    public static function typesForCategory(?string $category): array
    {
        $category ??= self::SUBMISSION_CATEGORY_JOURNAL;

        if (in_array($category, [
            self::SUBMISSION_CATEGORY_JOURNAL,
            self::SUBMISSION_CATEGORY_FACULTY_JOURNAL,
            self::SUBMISSION_CATEGORY_STUDENT_JOURNAL,
        ], true)) {
            return self::journalTypeOptions();
        }

        return self::TYPE_OPTIONS[$category] ?? self::TYPE_OPTIONS[self::SUBMISSION_CATEGORY_JOURNAL];
    }

    public static function allTypes(): array
    {
        return array_values(array_unique(array_merge(
            self::TYPE_OPTIONS[self::SUBMISSION_CATEGORY_RESEARCH],
            self::journalTypeOptions(),
            self::LEGACY_JOURNAL_TYPE_OPTIONS,
        )));
    }

    public static function inferCategoryFromType(?string $type): string
    {
        if ($type && in_array($type, array_merge(self::journalTypeOptions(), self::LEGACY_JOURNAL_TYPE_OPTIONS), true)) {
            return self::SUBMISSION_CATEGORY_JOURNAL;
        }

        return self::SUBMISSION_CATEGORY_RESEARCH;
    }

    public function authorNames(): array
    {
        if (is_array($this->authors)) {
            $authors = collect($this->authors)
                ->map(fn ($author) => trim((string) $author))
                ->filter()
                ->values()
                ->all();

            if ($authors !== []) {
                return $authors;
            }
        }

        $authorName = trim((string) $this->author_name);

        if ($authorName === '') {
            return [];
        }

        if (str_contains($authorName, ';')) {
            return collect(explode(';', $authorName))
                ->map(fn ($author) => trim($author))
                ->filter()
                ->values()
                ->all();
        }

        return [$authorName];
    }

    public function authorListLabel(): string
    {
        $authors = $this->authorNames();

        return $authors === [] ? 'Unknown author' : implode('; ', $authors);
    }

    public function cardAuthorName(): string
    {
        $authors = $this->authorNames();

        if ($authors === []) {
            return 'Unknown author';
        }

        return count($authors) > 1 ? $authors[0] . ' et al.' : $authors[0];
    }

    public function coordinatorStageLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Preparing',
            self::STATUS_DEAN_REVIEW => 'Submitted to Dean',
            self::STATUS_DEAN_APPROVED => 'Awaiting Forwarding to Admin',
            self::STATUS_PENDING => 'Forwarded to Admin for Publishing',
            self::STATUS_REJECTED => 'Needs Correction',
            self::STATUS_APPROVED => 'Published',
            self::STATUS_ARCHIVED => 'Archived',
            default => ucfirst((string) $this->status),
        };
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Preparing',
            self::STATUS_DEAN_REVIEW => 'Submitted to Dean',
            self::STATUS_DEAN_APPROVED => 'Awaiting Forwarding to Admin',
            self::STATUS_PENDING => 'Forwarded to Admin for Publishing',
            self::STATUS_APPROVED => 'Published',
            self::STATUS_REJECTED => 'Needs Correction',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }
}
