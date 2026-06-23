<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobEvent extends Model
{
    protected $fillable = [
        'job_id',
        'event_type',
        'message',
        'metadata',
    ];

    protected $casts = [
        'metadata'  => 'array'
    ];

    public function job()
    {
        return $this->belongsTo(ProspectingJob::class, 'job_id');
    }
}
