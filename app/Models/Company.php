<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Company",
 *     type="object",
 *     title="Company",
 *     required={"id", "name", "type"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Insurance Co."),
 *     @OA\Property(property="type", type="string", example="insurer"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T10:00:00Z")
 * )
 */
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'inn',
        'is_active',
        'description'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function insurerContracts()
    {
        return $this->hasMany(Contract::class, 'insurer_company_id');
    }

    public function reinsurerContracts()
    {
        return $this->hasMany(Contract::class, 'reinsurer_company_id');
    }
}