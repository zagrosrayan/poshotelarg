<?php

namespace Database\Seeders;

use App\Http\Service\TypeSlug;
use App\Models\ProfitManager;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $profit_manager_restaurant = ProfitManager::query()
            ->where('slug',TypeSlug::PROFIT_MANAGER_TYPE_RESTAURANT)->first()->id;
        $profit_manager_coffee_shop = ProfitManager::query()
            ->where('slug',TypeSlug::PROFIT_MANAGER_TYPE_COFFEE_SHOP)->first()->id;
        $profit_manager_coffee_shop_avin = ProfitManager::query()
            ->where('slug',TypeSlug::PROFIT_MANAGER_TYPE_COFFEE_SHOP_AVIN)->first()->id;
        $profit_manager_reception = ProfitManager::query()
            ->where('slug',TypeSlug::PROFIT_MANAGER_TYPE_RECEPTION)->first()->id;
        $users = [
            [
                'name' => 'پیمان ',
                'username' => 'peyman',
                'password' => '123456789',
                'profit_manager_id' => $profit_manager_restaurant
            ],


            [
                'name' => 'Mahdi Ehsan',
                'username' => '102',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_coffee_shop
            ],
            [
                'name' => 'Alireza Mirzaesmaeeli',
                'username' => '103',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_coffee_shop_avin
            ],
            [
                'name' => 'Sanaz Dehghani',
                'username' => '106',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_reception
            ],
            [
                'name' => 'Mahin Ahmadi Zadeh',
                'username' => '109',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
            [
                'name' => 'مهدي صادقی نژاد',
                'username' => '119',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
            [
                'name' => 'ali baghlani',
                'username' => '120',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
            [
                'name' => 'Sorosh',
                'username' => '122',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
            [
                'name' => 'فائزه السادات دهقان دهنوی',
                'username' => '126',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
            [
                'name' => 'Maziar khosraviani',
                'username' => '127',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
            [
                'name' => 'شمسی پهلوان',
                'username' => '128',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
            [
                'name' => 'Nasrin Zarei',
                'username' => '131',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
            [
                'name' => 'تابع',
                'username' => '134',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
            [
                'name' => 'کوروش خسرویانی',
                'username' => '136',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
            [
                'name' => 'ابوالفضل شکاري',
                'username' => '137',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
            [
                'name' => 'مرجان حق جو',
                'username' => '198',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
            [
                'name' => 'گلمحمدیان',
                'username' => '300',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
            [
                'name' => 'عاطفه دهقان چناری',
                'username' => '303',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
            [
                'name' => 'طاها احمدی',
                'username' => '307',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
            [
                'name' => 'زهرا جمشیدی',
                'username' => '308',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
            [
                'name' => 'امیر حسین نیکو منش',
                'username' => '309',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
            [
                'name' => 'احمدی زاده',
                'username' => '310',
                'password' => '12345',
                'profit_manager_id' => $profit_manager_restaurant
            ],
        ];
        User::query()->insert($users);
    }
}
