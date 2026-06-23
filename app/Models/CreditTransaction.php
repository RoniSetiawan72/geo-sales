<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditTransaction extends Model
{
    protected $table = 'credit_transactions';

    protected $fillable = [
        'tenant_id',
        'amount',
        'description',
        'reference_id',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
