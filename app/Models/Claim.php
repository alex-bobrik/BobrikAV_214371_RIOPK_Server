<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Claim",
 *     type="object",
 *     title="Claim",
 *     required={"id", "amount", "status", "filed_at"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="contract_id", type="integer", example=10),
 *     @OA\Property(property="amount", type="number", format="float", example=15000.50),
 *     @OA\Property(property="status", type="string", example="approved"),
 *     @OA\Property(property="filed_at", type="string", format="date-time", example="2023-01-15T10:00:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-15T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-16T12:30:00Z")
 * )
 */
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