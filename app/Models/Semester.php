<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasFactory;

    public const FIRST_SEMESTER = '1st';
    public const SECOND_SEMESTER = '2nd';

    protected $fillable = [
        'school_year',
        'semester',
        'start_date',
        'end_date',
        'is_active',
        'created_by',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public static function semesterOptions(): array
    {
        return [
            self::FIRST_SEMESTER,
            self::SECOND_SEMESTER,
        ];
    }

    public static function semesterLabels(): array
    {
        return [
            self::FIRST_SEMESTER => '1st Sem',
            self::SECOND_SEMESTER => '2nd Sem',
        ];
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'semester_enrollments')
            ->withPivot(['status', 'enrolled_at', 'enrolled_by'])
            ->withTimestamps();
    }

    public function enrollments()
    {
        return $this->hasMany(SemesterEnrollment::class);
    }

    public function currentUsers()
    {
        return $this->hasMany(User::class, 'current_semester_id');
    }

    public function researches()
    {
        return $this->hasMany(Research::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOpen($query)
    {
        return $query
            ->where('is_active', true)
            ->where(function ($inner) {
                $inner->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', now()->toDateString());
            });
    }

    public function scopeClosed($query)
    {
        return $query->where('is_active', false);
    }

    public function hasExpired(): bool
    {
        return $this->end_date !== null && $this->end_date->lt(now()->startOfDay());
    }

    public function isArchived(): bool
    {
        return ! $this->is_active || $this->hasExpired();
    }

    public function isOpen(): bool
    {
        return $this->is_active && ! $this->hasExpired();
    }

    public function getSemesterLabelAttribute(): string
    {
        return self::semesterLabels()[$this->semester] ?? $this->semester;
    }

    public function getLabelAttribute(): string
    {
        return trim($this->school_year . ' ' . $this->semester_label);
    }
}
