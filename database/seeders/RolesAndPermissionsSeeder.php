<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
//        $permissions_db = Permission::all();
//        foreach ($permissions_db as $permission) {
//            $permission->delete();
//        }
//        // ایجاد مجوزها
//        $permissions = [
//            'login', // دسترسی به پنل
//            'manage_articles', // مدیریت مقالات
//            'manage_foods', // مدیریت غذاها
//            'create_guest_order', // ثبت سفارش مهمان
//            'create_resident_order', // ثبت سفارش مقیم
//            'manage_roles', // مدیریت نقش‌ها
//            'view_users', // مشاهده کاربران
//            'manage_users', // مدیریت کاربران
//            'manage_printers', // مدیریت پرینترها
//            'food_report', // مدیریت پرینترها
//            'manage_types', // مدیریت انواع
//            'update_settings',
//            'complete_order',
//            'view_users',
//            'manage_orders', // مدیریت کامل سفارشات
//            'complete_order', // تکمیل سفارش
//            'delete_order', // حذف سفارش
//            'update_order', // بروزرسانی سفارش
//            'manage_discounts', // مدیریت تخفیف‌ها// تنظیمات سیستم
//            'view_logs', // مشاهده لاگ‌ها
//            'view_customer', // مشاهده لاگ‌ها
//            'manage_food', // مشاهده لاگ‌ها
//            'manage_profit_manager', // مشاهده لاگ‌ها
//            'manage_article', // مشاهده لاگ‌ها
//            'view_discount',
//            'view_printer',
//        ];
//
//        foreach ($permissions as $permission) {
//            Permission::firstOrCreate(['name' => $permission]);
//        }
        Permission::firstOrCreate(['name' => 'cost_control']);
        // ایجاد نقش‌ها
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo('cost_control');
//        $cashier = Role::firstOrCreate(['name' => 'cashier']);
//
//        $garsonRole = Role::firstOrCreate(['name' => 'garson']);
        $cost_control = Role::firstOrCreate(['name' => 'cost_control']);

//        // اختصاص مجوزها به نقش‌ها
//        $adminRole->givePermissionTo($permissions); // مدیر به همه مجوزها دسترسی دارد
//
//        $garsonPermissions = [
//            'login',
//            'create_guest_order',
//            'create_resident_order',
//            'complete_order',
//            'view_users',
//        ];
//        $cashierPermission = [
//            'login',
//            'create_guest_order',
//            'create_resident_order',
//            'complete_order',
//            'view_users',
//            'manage_orders', // مدیریت کامل سفارشات
//            'complete_order', // تکمیل سفارش
//            'delete_order', // حذف سفارش
//            'update_order', // بروزرسانی سفارش
//            'view_customer',
//'view_discount',
//            'view_printer',
//        ];
//        $financePermission = [
//            'login',
//            'manage_orders',
//            'food_report',
//            'view_customer',
//            'view_users',
//        ];
        $costControlPermissions = [
            'login',
            'manage_orders',
            'food_report',
            'view_customer',
            'view_users',
        ];
//        $financeRole->givePermissionTo($financePermission);
        $cost_control->givePermissionTo($costControlPermissions);
//        $cashier->givePermissionTo($cashierPermission); // مدیر به همه مجوزها دسترسی دارد
//        $garsonRole->givePermissionTo($garsonPermissions); // گارسون به مجوزهای محدود دسترسی دارد
    }
}
