<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentAccessKey extends Model
{
    protected $fillable = [
        'department',
        'semester',
        'school_year',
        'access_key',
        'access_key_plain',
        'expires_at',
        'archived_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'archived_at' => 'datetime',
        'is_active'  => 'boolean',
    ];

    public function displayStatus(): string
    {
        if ($this->expires_at?->isPast()) {
            return 'Expired';
        }

        if ($this->archived_at) {
            return 'Archived';
        }

        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function statusClass(): string
    {
        return match ($this->displayStatus()) {
            'Active' => 'active',
            'Expired' => 'expired',
            'Archived' => 'archived',
            default => 'inactive',
        };
    }
}
