<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'amount',
        'description',
        'status',
        'filed_at',
        'resolved_at',
    ];

    protected $casts = [
        'filed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}