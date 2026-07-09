<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'category' => 'log_status',
                'slug' => 'log-status-success',
                'name' => 'عملیات موفقیت امیز بود',
            ],
            [
                'category' => 'user_type',
                'slug' => 'user-type-cashier',
                'name' => 'صندوق دار',
            ],
            [
                'category' => 'log_status',
                'slug' => 'log-status-failed',
                'name' => 'عملیات با خطا مواجه شد ',
            ],
            [
                'category' => 'log_operation',
                'slug' => 'log-operation-login',
                'name' => 'ورود به سیستم',
            ],
            [
                'category' => 'log_operation',
                'slug' => 'log-operation-create',
                'name' => 'ایجاد',
            ],
            [
                'category' => 'log_operation',
                'slug' => 'log-operation-destroy',
                'name' => 'حذف',
            ],
            [
                'category' => 'log_operation',
                'slug' => 'log-operation-update',
                'name' => 'ویرایش',
            ],
            [
                'category' => 'log_operation',
                'slug' => 'log-operation-index',
                'name' => 'نمایش',
            ],
            [
                'category' => 'log_operation',
                'slug' => 'log-operation-use',
                'name' => 'استفاده',
            ],
            [
                'category' => 'discount_type',
                'slug' => 'discount_type_percentage_amount',
                'name' => 'تخفیف درصدی',
            ],
            [
                'category' => 'discount_type',
                'slug' => 'discount_type_fixed_amount',
                'name' => 'تخفیف ثابت',
            ],
            [
                'category' => 'customer_type',
                'slug' => 'customer_typeـvip',
                'name' => 'مشتری وی آی پی',
            ],
            [
                'category' => 'customer_type',
                'slug' => 'customer_typeـregular',
                'name' => 'مشتری معمولی',
            ],
            [
                'category' => 'order_status',
                'slug' => 'order-status-pending',
                'name' => 'ثبت موقت',
            ],
            [
                'category' => 'order_status',
                'slug' => 'order-status-complete',
                'name' => 'تکمیل سفارش',
            ],
            [
                'category' => 'payment_method',
                'slug' => 'payment-method-resident-user',
                'name' => '  مهمانان مقیم',
            ],
            [
                'category' => 'payment_method',
                'slug' => 'payment-method-cash',
                'name' => 'نقدی',
            ],
            [
                'category' => 'payment_method',
                'slug' => 'payment-method-saderat-pos',
                'name' => 'پوز صادرات',
            ],
            [
                'category' => 'payment_method',
                'slug' => 'payment-method-iranzamin-pos',
                'name' => 'پوز ایران زمین',
            ],
            [
                'category' => 'payment_method',
                'slug' => 'payment-method-mellat-pos',
                'name' => ' پوز ملت',
            ],
            [
                'category' => 'payment_method',
                'slug' => 'payment-method-refah-pos',
                'name' => 'پوز رفاه',
            ],
            [
                'category' => 'payment_method',
                'slug' => 'payment-method-etebary-pos',
                'name' => 'پوز اعتباری',
            ],
            // Printer Types
            [
                'name' => 'فیش پرینتر',
                'slug' => 'thermal-printer',
                'category' => 'printer_type'
            ],
            [
                'name' => ' Hp  پرینتر ',
                'slug' => 'laser-printer',
                'category' => 'printer_type'
            ],
        ];
        Type::query()->insert($types);

    }
}
