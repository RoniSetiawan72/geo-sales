<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantCredit extends Model
{
    protected $table = 'tenant_credits';
    protected $primaryKey = 'tenant_id';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'balance',
        'last_updated',
    ];

    protected $casts = [
        'last_updated'  => 'datetime'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
