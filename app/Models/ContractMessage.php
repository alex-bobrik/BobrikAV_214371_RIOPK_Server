<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'message',
        'created_by'
    ];

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

}
