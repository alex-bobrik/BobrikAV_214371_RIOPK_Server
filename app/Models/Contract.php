<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'insurer_id',
        'reinsurer_id',
        'premium',
        'coverage',
        'start_date',
        'end_date',
        'status',
        'created_by',
    ];

    public function insurer()
    {
        return $this->belongsTo(Company::class, 'insurer_id');
    }

    public function reinsurer()
    {
        return $this->belongsTo(Company::class, 'reinsurer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }
}