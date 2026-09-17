<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicSemester extends Model
{
    use HasFactory;

    public const FIRST_SEMESTER = 'First Semester';
    public const SECOND_SEMESTER = 'Second Semester';

    protected $fillable = [
        'semester',
        'school_year',
        'created_by',
        'archived_by',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public static function semesterOptions(): array
    {
        return [
            self::FIRST_SEMESTER,
            self::SECOND_SEMESTER,
        ];
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'academic_semester_user')
            ->withPivot(['assigned_at', 'assigned_by'])
            ->withTimestamps();
    }

    public function currentUsers()
    {
        return $this->hasMany(User::class, 'current_academic_semester_id');
    }

    public function researches()
    {
        return $this->hasMany(Research::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    public function getLabelAttribute(): string
    {
        return $this->semester . ' ' . $this->school_year;
    }
}
