<?php

namespace App\Models;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;

class User extends Authenticatable implements CanResetPassword
{
    use HasFactory, Notifiable, CanResetPasswordTrait;

    protected $fillable = [
        'name', 'middle_name', 'verification_documents', 'research_documents', 'email', 'password', 'role',
        'department', 'student_id', 'profile_photo', 'created_by',
        'is_active', 'is_approved', 'student_approved_by', 'student_approved_at', 'researcher_approved_by', 'researcher_approved_at',
        'is_department_dean', 'last_seen_at', 'capture_logs_seen_log_id',
        'policy_accepted_at', 'policy_version', 'policy_accepted_ip', 'policy_accepted_user_agent',
        'year_level', 'course_duration', 'graduation_year', 'researcher_end_date', 'graduated_at',
        'researcher_status', 'researcher_rejection_reason', 'researcher_rejected_at', 'researcher_applied_at',
        'department_access_department', 'department_access_school_year', 'department_access_semester',
        'department_access_expires_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
        'is_approved'       => 'boolean',
        'student_approved_at' => 'datetime',
        'researcher_approved_at' => 'datetime',
        'is_department_dean'=> 'boolean',
        'last_seen_at'      => 'datetime',
        'capture_logs_seen_log_id' => 'integer',
        'policy_accepted_at'=> 'datetime',
        'researcher_end_date' => 'date',
        'graduated_at'      => 'datetime',
        'researcher_rejected_at' => 'datetime',
        'researcher_applied_at' => 'datetime',
        'verification_documents' => 'array',
        'research_documents'     => 'array',
        'department_access_expires_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function researches()
    {
        return $this->hasMany(Research::class);
    }

    public function pinnedResearches()
    {
        return $this->belongsToMany(Research::class, 'research_pins')->withTimestamps();
    }

    public function researcherApprovedBy()
    {
        return $this->belongsTo(User::class, 'researcher_approved_by');
    }

    public function studentApprovedBy()
    {
        return $this->belongsTo(User::class, 'student_approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Role Checks ──────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDepartmentDean(): bool
    {
        return $this->isAdmin() && $this->is_department_dean === true;
    }

    public function isResearcher(): bool
    {
        return $this->role === 'researcher';
    }

    public function isGlobalAdmin(): bool
    {
        return $this->isAdmin() && ! $this->isDepartmentDean();
    }

    public function canManageDepartmentKeys(): bool
    {
        return $this->isGlobalAdmin();
    }

    public function canImportUsers(): bool
    {
        return $this->isDepartmentDean() && ! empty($this->department);
    }

    public function canApproveResearcher(User $researcher): bool
    {
        if (! $this->isAdmin() || $researcher->role !== 'researcher') {
            return false;
        }

        if ($this->isGlobalAdmin()) {
            return true;
        }

        return $this->isDepartmentDean()
            && ! empty($this->department)
            && $this->department === $researcher->department;
    }

    /**
     * PhilCST students — role='user' with a student_id.
     * They can view full research documents but cannot submit.
     */
    public function isPhilcstStudent(): bool
    {
        return $this->role === 'user'
            && $this->is_approved
            && ! empty($this->student_id);
    }

    /**
     * Who can view the full PDF/document of a research paper:
     * - Admins
     * - Approved researchers
     * - PhilCST students (role=user with student_id)
     *
     * Guests / unauthenticated users cannot — enforce this in your
     * ResearchController@viewFile / @download methods via auth middleware.
     */
    public function canViewFullDocument(): bool
    {
        if (! $this->is_active) return false;

        return match ($this->role) {
            'admin'      => true,
            'researcher' => $this->is_approved,
            'user'       => $this->isPhilcstStudent(),  // must have a student_id
            default      => false,
        };
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(2));
    }

    public function hasRecordedActivity(): bool
    {
        if (! is_null($this->last_seen_at)) {
            return true;
        }

        if (array_key_exists('researches_count', $this->attributes)) {
            return (int) $this->attributes['researches_count'] > 0;
        }

        return $this->researches()->exists();
    }

    // ── Graduation / Expiry ──────────────────────────────────────────────────

    public function isGraduated(): bool
    {
        if (! is_null($this->graduated_at)) {
            return true;
        }

        if ($this->role === 'researcher' && $this->researcher_end_date) {
            return $this->researcher_end_date->isPast();
        }

        return $this->role === 'researcher'
            && $this->graduation_year
            && $this->graduation_year < (int) date('Y');
    }

    public function deactivateIfGraduated(): bool
    {
        if (! $this->isGraduated()) {
            return false;
        }

        $updates = [];

        if ($this->is_active) {
            $updates['is_active'] = false;
        }

        if (! $this->graduated_at) {
            $updates['graduated_at'] = now();
        }

        if ($updates !== []) {
            $this->update($updates);
        }

        return true;
    }

    public function isGraduatingSoon(): bool
    {
        if ($this->role === 'researcher' && $this->researcher_end_date) {
            return $this->researcher_end_date->isFuture()
                && $this->researcher_end_date->year === (int) date('Y');
        }

        return $this->role === 'researcher'
            && $this->graduation_year
            && $this->graduation_year == (int) date('Y');
    }

    public function yearsUntilGraduation(): ?int
    {
        if ($this->researcher_end_date) {
            return $this->researcher_end_date->year - (int) date('Y');
        }

        if (! $this->graduation_year) return null;
        return $this->graduation_year - (int) date('Y');
    }

    public function computeGraduationYear(): ?int
    {
        if (! $this->year_level || ! $this->course_duration) {
            return null;
        }
        return (int) date('Y') + ($this->course_duration - $this->year_level);
    }

    // ── Researcher Status ────────────────────────────────────────────────────

    public function canSubmitResearch(): bool
    {
        if (! in_array($this->role, ['user', 'researcher'], true)) return false;
        if (! $this->is_active)           return false;
        if (! $this->is_approved)         return false;
        if ($this->role === 'researcher' && $this->isGraduated())  return false;
        return true;
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getYearLevelLabelAttribute(): string
    {
        return match ((int) $this->year_level) {
            1       => '1st Year',
            2       => '2nd Year',
            3       => '3rd Year',
            4       => '4th Year',
            default => 'N/A',
        };
    }

    public function getResearcherStatusLabelAttribute(): string
    {
        return $this->isGraduated() ? 'Graduated' : 'Active';
    }
}
