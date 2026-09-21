<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;


class Research extends Model
{
    use HasFactory, SoftDeletes;

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

    protected $fillable = [
        'title', 'abstract', 'author_name', 'authors', 'user_id', 'submission_category', 'issn', 'type',
        'semester_id', 'academic_semester_id',
        'department', 'course', 'program', 'year_published', 'keywords',
        'file_path', 'file_name', 'status', 'rejection_reason',
        'view_count', 'citation_copy_count', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
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

    public function pinnedByUsers()
    {
        return $this->belongsToMany(User::class, 'research_pins')->withTimestamps();
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
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
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
}
