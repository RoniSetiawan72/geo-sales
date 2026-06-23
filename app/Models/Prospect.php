<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prospect extends Model
{
    protected $fillable = [
        'job_id',
        'business_name',
        'address',
        'lat',
        'lng',
        'phone',
        'website',
        'rating',
        'reviews_count',
        'ai_score',
        'ai_classification',
        'ai_reasoning',
        'prospect_status',
    ];

    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'rating' => 'decimal:2',
        'ai_score' => 'integer',
    ];

    public function job()
    {
        return $this->belongsTo(ProspectingJob::class, 'job_id');
    }
}
