<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProspectingJobResult extends Model
{
    protected $fillable = [
        'prospecting_job_id',
        'business_name',
        'address_text',
        'phone_number',
        'website_url',
        'rating',
        'review_count',
        'lat',
        'lng',
        'geom'
    ];

    public function prospectingJob()
    {
        return $this->belongsTo(ProspectingJob::class);
    }
}
