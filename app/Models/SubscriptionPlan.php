<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'max_radius_km',
        'max_keywords',
        'max_prospects_per_job',
        'credits_per_month',
        'price',
    ];

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }
}
