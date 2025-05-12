<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Contract",
 *     type="object",
 *     title="Contract",
 *     required={"id", "type", "premium", "coverage", "start_date", "end_date", "insurer_id", "reinsurer_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="type", type="string", example="quota"),
 *     @OA\Property(property="premium", type="number", format="float", example=2000.00),
 *     @OA\Property(property="coverage", type="number", format="float", example=500000.00),
 *     @OA\Property(property="start_date", type="string", format="date", example="2023-01-01"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2024-01-01"),
 *     @OA\Property(property="insurer_id", type="integer", example=1),
 *     @OA\Property(property="reinsurer_id", type="integer", example=2),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T10:00:00Z")
 * )
 */
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