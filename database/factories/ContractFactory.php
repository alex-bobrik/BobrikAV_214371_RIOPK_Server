<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['quota', 'excess', 'facultative']),
            'insurer_id' => Company::factory(),
            'reinsurer_id' => Company::factory(),
            'premium' => $this->faker->randomFloat(2, 1000, 1000000),
            'coverage' => $this->faker->randomFloat(2, 100000, 10000000),
            'start_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'end_date' => $this->faker->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
            'status' => $this->faker->randomElement(['active', 'pending', 'canceled']),
            'created_by' => User::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}