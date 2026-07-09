<?php

namespace Database\Seeders;

use App\Http\Service\TypeSlug;
use App\Models\ProfitManager;
use Illuminate\Database\Seeder;

class ProfitManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $profit_managers = [
            [
                'name' => 'رستوران',
                'slug' => TypeSlug::PROFIT_MANAGER_TYPE_RESTAURANT,
            ],
            [
                'name' => 'کافی شاپ',
                'slug' => TypeSlug::PROFIT_MANAGER_TYPE_COFFEE_SHOP,
            ],
            [
                'name' => 'کافی شاپ آوین',
                'slug' => TypeSlug::PROFIT_MANAGER_TYPE_COFFEE_SHOP_AVIN,
            ],
            [
                'name' => 'پذیرش',
                'slug' => TypeSlug::PROFIT_MANAGER_TYPE_RECEPTION,
            ],
            [
                'name' => 'نانوایی',
                'slug' => TypeSlug::PROFIT_MANAGER_TYPE_BAKERY,
            ],
        ];
        ProfitManager::query()->insert($profit_managers);
    }
}
