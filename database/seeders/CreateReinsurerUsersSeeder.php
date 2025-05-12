<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CreateReinsurerUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reinsurers = Company::where('type', 'reinsurer')->get();

        $counter = 1;

        foreach ($reinsurers as $reinsurer) {
            $existing = User::where('company_id', $reinsurer->id)->first();
            if ($existing) continue;

            User::create([
                'name' => $reinsurer->name,
                'email' => "reinsurer{$counter}@test.com",
                'password' => Hash::make('12345'),
                'company_id' => $reinsurer->id,
                'role' => 'underwriter',
            ]);

            $counter++;
        }
    }
}
