<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompaniesTableSeeder extends Seeder
{
    public function run()
    {
        $companies = [
            [
                'id' => 1,
                'name' => 'Страховая Компания "АльфаСтрахование"',
                'address' => 'Россия',
                'phone' => '1234567',
                'email' => 'company@com.com',
                'inn' => 'IN1234',
                'description' => 'company description',
                'is_active' => true

            ],
            [
                'id' => 2,
                'name' => 'ПАО "СОГАЗ"',
                'address' => 'Россия',
                                'phone' => '1234567',
                'email' => 'company@com.com',
                'inn' => 'IN1234',
                'description' => 'company description',
                'is_active' => true
            ],
            [
                'id' => 3,
                'name' => 'ООО "СК "ВТБ Страхование"',
                'address' => 'Россия',
                                'phone' => '1234567',
                'email' => 'company@com.com',
                'inn' => 'IN1234',
                'description' => 'company description',
                'is_active' => true
            ],
            [
                'id' => 4,
                'name' => 'АО "Альянс"',
                'address' => 'Россия',
                                'phone' => '1234567',
                'email' => 'company@com.com',
                'inn' => 'IN1234',
                'description' => 'company description',
                'is_active' => true
            ],
            [
                'id' => 5,
                'name' => 'ПАО "Ингосстрах"',
                'address' => 'Россия',
                                'phone' => '1234567',
                'email' => 'company@com.com',
                'inn' => 'IN1234',
                'description' => 'company description',
                'is_active' => true
            ],
        ];

        foreach ($companies as $company) {
            Company::create($company);
        }
    }
}