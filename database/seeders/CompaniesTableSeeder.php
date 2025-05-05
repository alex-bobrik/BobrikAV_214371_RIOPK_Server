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
                'name' => 'Страховая Компания "АльфаСтрахование"',
                'type' => 'insurer',
                'country' => 'Россия'
            ],
            [
                'name' => 'ПАО "СОГАЗ"',
                'type' => 'insurer',
                'country' => 'Россия'
            ],
            [
                'name' => 'ООО "СК "ВТБ Страхование"',
                'type' => 'insurer',
                'country' => 'Россия'
            ],
            [
                'name' => 'АО "Альянс"',
                'type' => 'insurer',
                'country' => 'Россия'
            ],
            [
                'name' => 'ПАО "Ингосстрах"',
                'type' => 'insurer',
                'country' => 'Россия'
            ],
            [
                'name' => 'СК "Ренессанс Страхование"',
                'type' => 'insurer',
                'country' => 'Россия'
            ],
            [
                'name' => 'ООО "Страховая компания "Зетта Страхование"',
                'type' => 'insurer',
                'country' => 'Россия'
            ],
            // Перестраховочные компании
            [
                'name' => 'ООО "СК "РГС-Ре"',
                'type' => 'reinsurer',
                'country' => 'Россия'
            ],
            [
                'name' => 'АО "Национальная Перестраховочная Компания"',
                'type' => 'reinsurer',
                'country' => 'Россия'
            ],
            [
                'name' => 'ООО "Перестраховочная компания "Евро-Полис"',
                'type' => 'reinsurer',
                'country' => 'Россия'
            ]
        ];

        foreach ($companies as $company) {
            Company::create($company);
        }
    }
}