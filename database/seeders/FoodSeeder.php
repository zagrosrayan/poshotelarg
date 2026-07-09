<?php

namespace Database\Seeders;

use App\Http\Service\TypeSlug;
use App\Models\Article;
use App\Models\Food;
use App\Models\ProfitManager;
use Illuminate\Database\Seeder;

class FoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $article_main_food = Article::query()->where('slug', TypeSlug::ARTICLE_TYPE_MAIN_FOOD)->first()->id;
        $article_mini_bar = Article::query()->where('slug', TypeSlug::ARTICLE_TYPE_MINI_BAR)->first()->id;
        $article_fast_food = Article::query()->where('slug', TypeSlug::ARTICLE_TYPE_FAST_FOOD)->first()->id;
        $article_appetizer = Article::query()->where('slug', TypeSlug::ARTICLE_TYPE_APPETIZER)->first()->id;
        $article_drink = Article::query()->where('slug', TypeSlug::ARTICLE_TYPE_DRINK)->first()->id;
        $article_sea_food = Article::query()->where('slug', TypeSlug::ARTICLE_TYPE_SEA_FOOD)->first()->id;
        $article_coffee_based_drink = Article::query()->where('slug', TypeSlug::ARTICLE_TYPE_COFFEE_BASED_DRINK)->first()->id;
        $article_BREW_drink = Article::query()->where('slug', TypeSlug::ARTICLE_TYPE_BREW_DRINK)->first()->id;
        $article_hot_drink = Article::query()->where('slug', TypeSlug::ARTICLE_TYPE_HOT_DRINK)->first()->id;
        $article_cold_coffee_based_drink = Article::query()->where('slug', TypeSlug::ARTICLE_TYPE_COLD_COFFEE_BASED_DRINK)->first()->id;
        $article_shake = Article::query()->where('slug', TypeSlug::ARTICLE_TYPE_SHAKE)->first()->id;
        $article_smoothie = Article::query()->where('slug', TypeSlug::ARTICLE_TYPE_SMOOTHIE)->first()->id;
        $article_mock_tail = Article::query()->where('slug', TypeSlug::ARTICLE_TYPE_MOCK_TAIL)->first()->id;
        $article_cold_drink = Article::query()->where('slug', TypeSlug::ARTICLE_TYPE_COLD_DRINK)->first()->id;
        $article_desserts = Article::query()->where('slug', TypeSlug::ARTICLE_TYPE_DESSERTS)->first()->id;
        $article_cake = Article::query()->where('slug',
            TypeSlug::ARTICLE_TYPE_CAKE)->first()->id;

        $profit_manager = ProfitManager::query()->where('slug', TypeSlug::PROFIT_MANAGER_TYPE_RESTAURANT)->first()->id;
        $profit_manager_coffee_shop_avin = ProfitManager::query()->where('slug', TypeSlug::PROFIT_MANAGER_TYPE_COFFEE_SHOP_AVIN)->first()->id;
        $profit_manager_coffee_shop = ProfitManager::query()->where('slug', TypeSlug::PROFIT_MANAGER_TYPE_COFFEE_SHOP)->first()->id;
        $profit_manager_reception = ProfitManager::query()->where('slug', TypeSlug::PROFIT_MANAGER_TYPE_RECEPTION)->first()->id;

        $foods = [
            // Restaurant profit
            //main_foods
            [
                'name' => 'چلو زعفرانی',
                'price' => '100000',
                'slug' => TypeSlug::SAFFRON_RISE,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
//            [
//                'name' => 'دلستر',
//                'price' => '30000',
//                'slug' => TypeSlug::BEER,
//                'article_id' => $article_mini_bar,
//                'profit_manager_id' => $profit_manager_reception,
//            ],
//            [
//                'name' => 'نوشابه قوطی',
//                'price' => '26400',
//                'slug' => TypeSlug::COKE,
//                'article_id' => $article_mini_bar,
//                'profit_manager_id' => $profit_manager_reception,
//            ],
            [
                'name' => 'ابمیوه',
                'price' => '23000',
                'slug' => TypeSlug::FRUIT_JUICE,
                'article_id' => $article_mini_bar,
                'profit_manager_id' => $profit_manager_reception,
            ],
//            [
//                'name' => 'اب معدنی ',
//                'price' => '8000',
//                'slug' => TypeSlug::MINERAL_WATER,
//                'article_id' => $article_mini_bar,
//                'profit_manager_id' => $profit_manager_reception,
//            ],
            [
                'name' => 'ویفر کاکاهویی هیس',
                'price' => '10000',
                'slug' => TypeSlug::HISS,
                'article_id' => $article_mini_bar,
                'profit_manager_id' => $profit_manager_reception,
            ],
            [
                'name' => 'پسته',
                'price' => '43000',
                'slug' => TypeSlug::PISTACHIO,
                'article_id' => $article_mini_bar,
                'profit_manager_id' => $profit_manager_reception,
            ],
            [
                'name' => 'میوه خشک',
                'price' => '45000',
                'slug' => TypeSlug::DRIED_FRUIT,
                'article_id' => $article_mini_bar,
                'profit_manager_id' => $profit_manager_reception,
            ],
            [
                'name' => 'پاپل کوکی',
                'price' => '10000',
                'slug' => TypeSlug::POPEL,
                'article_id' => $article_mini_bar,
                'profit_manager_id' => $profit_manager_reception,
            ],
            [
                'name' => 'شکلات نانی',
                'price' => '3000',
                'slug' => TypeSlug::NANI_CHOCOLATE,
                'article_id' => $article_mini_bar,
                'profit_manager_id' => $profit_manager_reception,
            ],
            [
                'name' => 'ویفر رولی',
                'price' => '12000',
                'slug' => TypeSlug::ROLL_WAFER,
                'article_id' => $article_mini_bar,
                'profit_manager_id' => $profit_manager_reception,
            ],
            [
                'name' => 'بادام',
                'price' => '38000',
                'slug' => TypeSlug::ALMOND,
                'article_id' => $article_mini_bar,
                'profit_manager_id' => $profit_manager_reception,
            ],
            [
                'name' => 'کباب درباری',
                'price' => '300000',
                'slug' => TypeSlug::DARBARI_KEBAB,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'کباب کوبیده (2 سیخ)',
                'price' => '260000',
                'slug' => TypeSlug::KOOBIDEH_KEBAB_2,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'کباب کوبیده (تک سیخ)',
                'price' => '130000',
                'slug' => TypeSlug::KOOBIDEH_KEBAB,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'کباب لقمه',
                'price' => '270000',
                'slug' => TypeSlug::SLICED_MINCED_KEBAB,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'کباب برگ',
                'price' => '490000',
                'slug' => TypeSlug::LAMB_FILET_BARBEQUE,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'کباب سلطانی',
                'price' => '610000',
                'slug' => TypeSlug::SOLTANI_KEBAB,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'شیشلیک',
                'price' => '750000',
                'slug' => TypeSlug::SHISHLIK,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'کباب بختیاری',
                'price' => '360000',
                'slug' => TypeSlug::BAKHTIARI_KEBAB,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'خوراک مرغ',
                'price' => '170000',
                'slug' => TypeSlug::CHICKEN_DISH,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'جوجه کباب زعفرانی',
                'price' => '190000',
                'slug' => TypeSlug::SAFFRON_CHICKEN_FILET_BARBEQUE,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'جوجه با استخوان',
                'price' => '220000',
                'slug' => TypeSlug::CHICKEN_BARBEQUE_WITH_BONE,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'خوراک ماهیچه',
                'price' => '795000',
                'slug' => TypeSlug::LAMB_SHANK_DISH,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'خورشت فسنجان',
                'price' => '240000',
                'slug' => TypeSlug::FESENJON_STEW,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'خورشت قیمه یزدی',
                'price' => '270000',
                'slug' => TypeSlug::YAZDI_GHEYMEH_STEW,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'خورشت سبزی',
                'price' => '200000',
                'slug' => TypeSlug::KHORESHT_SABZI,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'شیرین پلو',
                'price' => '130000',
                'slug' => TypeSlug::SWEET_RICE,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'سبزی پلو',
                'price' => '105000',
                'slug' => TypeSlug::VEGETABLE_RICE,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'باقالی پلو',
                'price' => '115000',
                'slug' => TypeSlug::BROAD_BEANS_RICE,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'زرشک پلو',
                'price' => '105000',
                'slug' => TypeSlug::BARBERRY_RICE,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],

            [
                'name' => 'خوراک سبزیجات',
                'price' => '150000',
                'slug' => TypeSlug::VEGAN_DISH,
                'article_id' => $article_main_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'میگو کبابی',
                'price' => '450000',
                'slug' => TypeSlug::GRILLED_SHRIMP,
                'article_id' => $article_sea_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'میگو پلو',
                'price' => '420000',
                'slug' => TypeSlug::SHRIMP_WITH_RICE,
                'article_id' => $article_sea_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'میگو سوخاری',
                'price' => '470000',
                'slug' => TypeSlug::FRIED_SHRIMP,
                'article_id' => $article_sea_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'ماهی شیرکبابی',
                'price' => '505000',
                'slug' => TypeSlug::GRILLED_FISH,
                'article_id' => $article_sea_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'قلیه ماهی',
                'price' => '330000',
                'slug' => TypeSlug::GHALIEH_MAHI,
                'article_id' => $article_sea_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'ماهی قزل آلا',
                'price' => '340000',
                'slug' => TypeSlug::TROUT_FISH,
                'article_id' => $article_sea_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'ماهی مخصوص ارگ',
                'price' => '450000',
                'slug' => TypeSlug::ARG_SPECIAL_FISH,
                'article_id' => $article_sea_food,
                'profit_manager_id' => $profit_manager,
            ],

            //appetizer
            [
                'name' => 'سالاد بار',
                'price' => '150000',
                'slug' => TypeSlug::SALAD_BAR,
                'article_id' => $article_appetizer,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'سالاد سزار',
                'price' => '210000',
                'slug' => TypeSlug::CAESAR_SALAD,
                'article_id' => $article_appetizer,
                'profit_manager_id' => $profit_manager_coffee_shop_avin,
            ],
            [
                'name' => 'سالاد فصل',
                'price' => '50000',
                'slug' => TypeSlug::SEASON_SALAD,
                'article_id' => $article_appetizer,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'سوپ جو',
                'price' => '60000',
                'slug' => TypeSlug::BARLEY_SOUP,
                'article_id' => $article_appetizer,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'سیب زمینی سرخ کرده',
                'price' => '110000',
                'slug' => TypeSlug::FRENCH_FRIES,
                'article_id' => $article_appetizer,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'نان سیر',
                'price' => '180000',
                'slug' => TypeSlug::GARLIC_BREAD,
                'article_id' => $article_appetizer,
                'profit_manager_id' => $profit_manager_coffee_shop_avin,
            ],
            [
                'name' => 'سیب زمینی سرخ کرده',
                'price' => '95000',
                'slug' => TypeSlug::FRENCH_FRIES_CHEAPER,
                'article_id' => $article_appetizer,
                'profit_manager_id' => $profit_manager_coffee_shop_avin,
            ],

            //drink
            [
                'name' => 'نوشابه',
                'price' => '0', //Approved rate
                'slug' => TypeSlug::COKE,
                'article_id' => $article_drink,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'فانتا',
                'price' => '0', //Approved rate
                'slug' => TypeSlug::FANTA,
                'article_id' => $article_drink,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'دوغ',
                'price' => '0', //Approved rate
                'slug' => TypeSlug::DRINKING_YOGHURT,
                'article_id' => $article_drink,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'دلستر',
                'price' => '0', //Approved rate
                'slug' => TypeSlug::BEER,
                'article_id' => $article_drink,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'آب معدنی (کوچک)',
                'price' => '0', //Approved rate
                'slug' => TypeSlug::MINERAL_WATER,
                'article_id' => $article_drink,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'آب پرتقال طبیعی',
                'price' => '130000',
                'slug' => TypeSlug::NATURAL_ORANGE_JUICE,
                'article_id' => $article_drink,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'موهیتو',
                'price' => '95000',
                'slug' => TypeSlug::MOJITO,
                'article_id' => $article_drink,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'لیموناد',
                'price' => '92000',
                'slug' => TypeSlug::LIMONADE,
                'article_id' => $article_drink,
                'profit_manager_id' => $profit_manager,
            ],

            //fastfood
            [
                'name' => 'بیف استراگانف',
                'price' => '460000',
                'slug' => TypeSlug::BEEF_STROGANOFF,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'چیکن استراگانف',
                'price' => '280000',
                'slug' => TypeSlug::CHICKEN_STROGANOFF,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'استیک گوشت با سس قارچ',
                'price' => '580000',
                'slug' => TypeSlug::BEEF_STEAK_WITH_MUSHROOM_SAUCE,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'استیک مرغ',
                'price' => '330000',
                'slug' => TypeSlug::CHICKEN_FILET_STEAK,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'پیتزا مخصوص',
                'price' => '280000',
                'slug' => TypeSlug::SPECIAL_PIZZA,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'پیتزا سبزیجات',
                'price' => '220000',
                'slug' => TypeSlug::VEGAN_PIZZA,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'پیتزا پپرونی',
                'price' => '260000',
                'slug' => TypeSlug::PEPPERONI_PIZZA,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'پیتزا رست بیف',
                'price' => '390000',
                'slug' => TypeSlug::ROAST_BEEF_PIZZA,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'پیتزا مرغ',
                'price' => '270000',
                'slug' => TypeSlug::CHICKEN_PIZZA,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'همبرگر',
                'price' => '230000',
                'slug' => TypeSlug::BURGER,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager_coffee_shop_avin,
            ],
            [
                'name' => 'چیزبرگر',
                'price' => '250000',
                'slug' => TypeSlug::CHEESEBURGER,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager_coffee_shop_avin,
            ],
            [
                'name' => 'شینسل مرغ',
                'price' => '240000',
                'slug' => TypeSlug::CHICKEN_SCHNITZEL,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'مرغ سوخاری سه تیکه',
                'price' => '240000',
                'slug' => TypeSlug::FRIED_CHICKEN_3_PIECES,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'مرغ سوخاری پنج تیکه',
                'price' => '280000',
                'slug' => TypeSlug::FRIED_CHICKEN_5_PIECES,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager,
            ],
            [
                'name' => 'پنه چیکن آلفردو',
                'price' => '280000',
                'slug' => TypeSlug::CHICKEN_ALFREDO_PASTA,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager_coffee_shop_avin,
            ],
            [
                'name' => 'پنه بیف آلفردو',
                'price' => '460000',
                'slug' => TypeSlug::BEEF_ALFREDO_PASTA,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager_coffee_shop_avin,
            ],

            [
                'name' => 'پیتزا سیر و استیک',
                'price' => '450000',
                'slug' => TypeSlug::GARLIC_STEAK_PIZZA,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager_coffee_shop_avin,
            ],
            [
                'name' => 'پیتزا چیکن لاور ',
                'price' => '330000',
                'slug' => TypeSlug::CHICKEN_LOVER_PIZZA,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager_coffee_shop_avin,
            ],
            [
                'name' => 'پیتزا اسپشیال ',
                'price' => '410000',
                'slug' => TypeSlug::SPECIAL_PIZZA_MORE_EXPENSIVE,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager_coffee_shop_avin,
            ],
            [
                'name' => 'پیتزا چیکن پستو ',
                'price' => '350000',
                'slug' => TypeSlug::CHICKEN_PESTO_PIZZA,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager_coffee_shop_avin,
            ],
            [
                'name' => 'پیتزا کارنه ',
                'price' => '450000',
                'slug' => TypeSlug::CARNE_PIZZA,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager_coffee_shop_avin,
            ],
            [
                'name' => 'پیتزا پپرونی ',
                'price' => '310000',
                'slug' => TypeSlug::PEPPERONI_PIZZA_MORE_EXPENSIVE,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager_coffee_shop_avin,
            ],
            [
                'name' => 'پیتزا تورنادو ',
                'price' => '420000',
                'slug' => TypeSlug::TORNADO_PIZZA,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager_coffee_shop_avin,
            ],
            [
                'name' => 'پیتزا زبان',
                'price' => '450000',
                'slug' => TypeSlug::BEEF_TONGUE_PIZZA,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager_coffee_shop_avin,
            ],
            [
                'name' => 'پیتزا وجترین ',
                'price' => '250000',
                'slug' => TypeSlug::VEGETARIAN_PIZZA_MORE_EXPENSIVE,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager_coffee_shop_avin,
            ],
            [
                'name' => 'کلزونه رست بیف ',
                'price' => '420000',
                'slug' => TypeSlug::BEEF_CHEESE_CALZONE,
                'article_id' => $article_fast_food,
                'profit_manager_id' => $profit_manager_coffee_shop_avin,
            ],

            //Coffee Shop Profit
            //coffee based drink

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            [
                'name' => 'دوپیو',
                'price' => '69000',
                'slug' => TypeSlug::DOPPIO,
                'article_id' => $article_coffee_based_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'لاته',
                'price' => '103000',
                'slug' => TypeSlug::LATTE,
                'article_id' => $article_coffee_based_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'آمریکانو',
                'price' => '70000',
                'slug' => TypeSlug::AMERICANO,
                'article_id' => $article_coffee_based_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'کاپوچینو',
                'price' => '103000',
                'slug' => TypeSlug::CAPPUCCINO,
                'article_id' => $article_coffee_based_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'فلاور لاته',
                'price' => '110000',
                'slug' => TypeSlug::FLOWER_LATTE,
                'article_id' => $article_coffee_based_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'موکا',
                'price' => '127000',
                'slug' => TypeSlug::MOCHA,
                'article_id' => $article_coffee_based_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'پیکولو',
                'price' => '154000',
                'slug' => TypeSlug::PICCOLO,
                'article_id' => $article_coffee_based_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'کارامل ماکیاتو',
                'price' => '115000',
                'slug' => TypeSlug::CARAMEL_MACCHIATO,
                'article_id' => $article_coffee_based_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'ماکیاتو',
                'price' => '82000',
                'slug' => TypeSlug::MACCHIATO,
                'article_id' => $article_coffee_based_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            //BREW
            [
                'name' => 'قهوه ترک',
                'price' => '70000',
                'slug' => TypeSlug::TURKISH_COFFEE,
                'article_id' => $article_BREW_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'وی سیکستی',
                'price' => '97000',
                'slug' => TypeSlug::V60,
                'article_id' => $article_BREW_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'کمکس',
                'price' => '75000',
                'slug' => TypeSlug::CHEMEX,
                'article_id' => $article_BREW_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'فرنچ',
                'price' => '75000',
                'slug' => TypeSlug::FRENCH,
                'article_id' => $article_BREW_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            //Hot Drink
            [
                'name' => 'چای ساده تک نفره',
                'price' => '43000',
                'slug' => TypeSlug::TEA_1_PERSON,
                'article_id' => $article_hot_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'چای دو نفره',
                'price' => '65000',
                'slug' => TypeSlug::TEA_2_PERSONS,
                'article_id' => $article_hot_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'دمنوش چای ترش',
                'price' => '64000',
                'slug' => TypeSlug::SOUR_TEA,
                'article_id' => $article_hot_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'دمنوش شاداب',
                'price' => '82000',
                'slug' => TypeSlug::SHADAB_HERBAL_TEA,
                'article_id' => $article_hot_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'دمنوش مه کوهستان',
                'price' => '82000',
                'slug' => TypeSlug::MEH_KUHESTAN_HERBAL_TEA,
                'article_id' => $article_hot_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'دمنوش آرامش',
                'price' => '82000',
                'slug' => TypeSlug::ARAMESH_HERBAL_TEA,
                'article_id' => $article_hot_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'بابونه',
                'price' => '64000',
                'slug' => TypeSlug::CHAMOMILE,
                'article_id' => $article_hot_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'دمنوش آویشن',
                'price' => '64000',
                'slug' => TypeSlug::THYME,
                'article_id' => $article_hot_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'گل گاوزبان',
                'price' => '64000',
                'slug' => TypeSlug::BORAGE,
                'article_id' => $article_hot_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'چاکلت لیدی',
                'price' => '106000',
                'slug' => TypeSlug::CHOCOLATE_LADY,
                'article_id' => $article_hot_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'هات چاکلت',
                'price' => '88000',
                'slug' => TypeSlug::HOT_CHOCOLATE,
                'article_id' => $article_hot_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'چای ماسالا',
                'price' => '95000',
                'slug' => TypeSlug::MASALA_TEA,
                'article_id' => $article_hot_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            //Cold coffee based drink
            [
                'name' => 'کلد برو تادی',
                'price' => '73000',
                'slug' => TypeSlug::COLD_BREW_TODDY,
                'article_id' => $article_cold_coffee_based_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'آفوگاتو ',
                'price' => '110000',
                'slug' => TypeSlug::AFOGATO,
                'article_id' => $article_cold_coffee_based_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'آیس آمریکانو',
                'price' => '86000',
                'slug' => TypeSlug::ICED_AMERICANO,
                'article_id' => $article_cold_coffee_based_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'آیس لاته',
                'price' => '113000',
                'slug' => TypeSlug::ICED_LATTE,
                'article_id' => $article_cold_coffee_based_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'آیس موکا',
                'price' => '127000',
                'slug' => TypeSlug::ICED_MOCHA,
                'article_id' => $article_cold_coffee_based_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],

            //Shake
            [
                'name' => 'شیک پاپکورن',
                'price' => '187000',
                'slug' => TypeSlug::POPCORN,
                'article_id' => $article_shake,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'شیک لبنانی',
                'price' => '156000',
                'slug' => TypeSlug::LEBANESE,
                'article_id' => $article_shake,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'شیک شیراز',
                'price' => '171000',
                'slug' => TypeSlug::SHIRAZ,
                'article_id' => $article_shake,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'شیک کلاسیک چاکلت',
                'price' => '110000',
                'slug' => TypeSlug::CLASSIC_CHOCOLATE,
                'article_id' => $article_shake,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'شیک نوتلا',
                'price' => '139000',
                'slug' => TypeSlug::NUTELLA,
                'article_id' => $article_shake,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'شیک اورئو',
                'price' => '119000',
                'slug' => TypeSlug::OREO,
                'article_id' => $article_shake,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'شیک لوتوس',
                'price' => '146000',
                'slug' => TypeSlug::LOTUS,
                'article_id' => $article_shake,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'کافی شیک',
                'price' => '142000',
                'slug' => TypeSlug::COFFEE,
                'article_id' => $article_shake,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            //Smoothie
            [
                'name' => 'اسموتی پینک سامر',
                'price' => '148000',
                'slug' => TypeSlug::PINK_SUMMER,
                'article_id' => $article_smoothie,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'اسموتی انبه',
                'price' => '146000',
                'slug' => TypeSlug::MANGO,
                'article_id' => $article_smoothie,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'اسموتی توت فرنگی',
                'price' => '156000',
                'slug' => TypeSlug::STRAWBERRY,
                'article_id' => $article_smoothie,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'مارگاریتا',
                'price' => '159000',
                'slug' => TypeSlug::MARGARITA,
                'article_id' => $article_smoothie,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            //Mock tail
            [
                'name' => 'کوبانو موهیتو',
                'price' => '95000',
                'slug' => TypeSlug::CUBANO_MOJITO,
                'article_id' => $article_mock_tail,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'رد موهیتو',
                'price' => '123000',
                'slug' => TypeSlug::RED_MOJITO,
                'article_id' => $article_mock_tail,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'باربری چیلی',
                'price' => '125000',
                'slug' => TypeSlug::BURBERRY_CHILLY,
                'article_id' => $article_mock_tail,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            //Cold drink
            [
                'name' => 'آب پرتقال',
                'price' => '130000',
                'slug' => TypeSlug::ORANGE_JUICE,
                'article_id' => $article_cold_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'آب سیب',
                'price' => '130000',
                'slug' => TypeSlug::APPLE_JUICE,
                'article_id' => $article_cold_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'خیار سکنجبین',
                'price' => '80000',
                'slug' => TypeSlug::CUCUMBER_SEKANJABIN,
                'article_id' => $article_cold_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'کرشمه',
                'price' => '140000',
                'slug' => TypeSlug::KERESHME,
                'article_id' => $article_cold_drink,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            //Desserts
            [
                'name' => 'آب هویج بستنی',
                'price' => '110000',
                'slug' => TypeSlug::CARROT_JUICE_WITH_ICE_CREAM,
                'article_id' => $article_desserts,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'دسر شیراز',
                'price' => '110000',
                'slug' => TypeSlug::SHIRAZ_DESSERT,
                'article_id' => $article_desserts,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'دسر یزد',
                'price' => '152000',
                'slug' => TypeSlug::YAZD_DESSERT,
                'article_id' => $article_desserts,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            //Cake
            [
                'name' => 'کیک شکلاتی',
                'price' => '102000',
                'slug' => TypeSlug::CHOCOLATE_CAKE,
                'article_id' => $article_cake,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => ' چیزکیک',
                'price' => '152000',
                'slug' => TypeSlug::CHEESE_CAKE,
                'article_id' => $article_cake,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'رد ولوت',
                'price' => '102000',
                'slug' => TypeSlug::RED_VELVET,
                'article_id' => $article_cake,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],
            [
                'name' => 'دولچه فرانسه',
                'price' => '105000',
                'slug' => TypeSlug::FRENCH_DOLCHE,
                'article_id' => $article_cake,
                'profit_manager_id' => $profit_manager_coffee_shop,
            ],


        ];
        Food::query()->insert($foods);
    }
}
