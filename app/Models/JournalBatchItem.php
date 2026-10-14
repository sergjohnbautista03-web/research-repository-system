<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalBatchItem extends Model
{
    protected $fillable = ['batch_id', 'source_research_id', 'journal_id'];

    public function source()
    {
        return $this->belongsTo(Research::class, 'source_research_id');
    }

    public function journal()
    {
        return $this->belongsTo(Research::class, 'journal_id');
    }
}
