<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'company_name',
        'email',
        'phone',
        'address',
        'subscription_plan_id',
        'subscription_start',
        'subscription_end',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'subscription_start' => 'date',
        'subscription_end' => 'date',
    ];

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function prospectingJobs()
    {
        return $this->hasMany(ProspectingJob::class);
    }

    public function credits()
    {
        return $this->hasOne(TenantCredit::class);
    }

    public function creditTransacctions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

}
