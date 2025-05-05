<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'amount',
        'type',
        'status',
        'payment_date',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}