<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'country',
        'description',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function insurerContracts()
    {
        return $this->hasMany(Contract::class, 'insurer_id');
    }

    public function reinsurerContracts()
    {
        return $this->hasMany(Contract::class, 'reinsurer_id');
    }
}