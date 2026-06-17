<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResearchDownloadLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'research_id',
        'user_id',
        'downloader_name',
        'downloader_email',
        'downloader_role',
        'downloader_department',
        'download_scope',
        'ip_address',
        'user_agent',
    ];

    public function research()
    {
        return $this->belongsTo(Research::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
