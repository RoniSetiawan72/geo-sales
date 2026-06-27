<?php

namespace App\Models;

use App\Casts\PostgresArrayCast;
use Illuminate\Database\Eloquent\Model;

class ProspectingJob extends Model
{
    protected $table = 'prospecting_jobs';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'product_id',
        'product_name',
        'product_description',
        'product_usp',
        'target_keywords',
        'center_lat',
        'center_lng',
        'radius_meters',
        'status',
        'total_extracted',
        'total_scored',
        'credits_used',
        'ai_analysis',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'target_keywords' => PostgresArrayCast::class,
        'ai_analysis'     => 'array',
        'center_lat' => 'decimal:8',
        'center_lng' => 'decimal:8',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function prospects()
    {
        return $this->hasMany(Prospect::class, 'job_id');
    }

    public function events()
    {
        return $this->hasMany(JobEvent::class, 'job_id');
    }

    public function results()
    {
        return $this->hasMany(ProspectingJobResult::class);
    }
}
