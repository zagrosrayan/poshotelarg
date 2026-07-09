<?php

namespace Database\Seeders;

use App\Http\Service\TypeSlug;
use App\Models\Article;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $articles = [
            [
                'name' => 'غذای اصلی',
                'slug' => TypeSlug::ARTICLE_TYPE_MAIN_FOOD,
                'image' => asset('images/1.jpg'),
            ],
            [
                'name' => 'غذای دریایی',
                'slug' => TypeSlug::ARTICLE_TYPE_SEA_FOOD,
                'image' => asset('images/2.jpg'),
            ],
            [
                'name' => 'پیش غذا',
                'slug' => TypeSlug::ARTICLE_TYPE_APPETIZER,
                'image' => asset('images/3.jpg'),
            ],
            [
                'name' => 'نوشیدنی',
                'slug' => TypeSlug::ARTICLE_TYPE_DRINK,
                'image' => asset('images/3.jpg'),
            ],
            [
                'name' => 'فست فود فرنگی',
                'slug' => TypeSlug::ARTICLE_TYPE_FAST_FOOD,
                'image' => asset('images/2.jpg'),
            ],
            [
                'name' => 'بر پایه قهوه',
                'slug' => TypeSlug::ARTICLE_TYPE_COFFEE_BASED_DRINK,
                'image' => '2.jpg',
            ],
            [
                'name' => 'دمی',
                'slug' => TypeSlug::ARTICLE_TYPE_BREW_DRINK,
                'image' => asset('images/3.jpg'),

            ],
            [
                'name' => 'نوشیدنی گرم',
                'slug' => TypeSlug::ARTICLE_TYPE_HOT_DRINK,
                'image' => asset('images/3.jpg'),

            ],
            [
                'name' => 'مینی بار',
                'slug' => TypeSlug::ARTICLE_TYPE_MINI_BAR,
                'image' => asset('images/3.jpg'),

            ],
            [
                'name' => 'نوشیدنی سرد بر پایه قهوه',
                'slug' => TypeSlug::ARTICLE_TYPE_COLD_COFFEE_BASED_DRINK,
                'image' => asset('images/3.jpg'),

            ],
            [
                'name' => 'شیک ها',
                'slug' => TypeSlug::ARTICLE_TYPE_SHAKE,
                'image' => asset('images/3.jpg'),

            ],
            [
                'name' => 'اسموتی',
                'slug' => TypeSlug::ARTICLE_TYPE_SMOOTHIE,
                'image' => asset('images/3.jpg'),

            ],
            [
                'name' => 'ماکتل',
                'slug' => TypeSlug::ARTICLE_TYPE_MOCK_TAIL,
                'image' => asset('images/3.jpg'),

            ],
            [
                'name' => 'نوشیدنی سرد',
                'slug' => TypeSlug::ARTICLE_TYPE_COLD_DRINK,
                'image' => asset('images/2.jpg'),

            ],
            [
                'name' => 'دسر',
                'slug' => TypeSlug::ARTICLE_TYPE_DESSERTS,
                'image' => asset('images/1.jpg'),

            ],
            [
                'name' => 'کیک',
                'slug' => TypeSlug::ARTICLE_TYPE_CAKE,
                'image' => asset('images/3.jpg'),

            ],
        ];

        Article::query()->insert($articles);
    }
}
