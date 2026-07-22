<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClubSettingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\NextPurchaseDiscountController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\ProfitManagerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\UserController;
use App\Models\User;
use App\Service\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AISearchController;
use Melipayamak\MelipayamakApi;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('test',function(){
    \App\Models\NextPurchaseDiscount::query()->get()->each(function ($q){
        $q->delete();
    });
});
Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->name('auth.login'); // ورود
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:garson|cashier')->group(function () {
            Route::prefix('order')->group(function () {
                Route::post('create', [OrderController::class, 'createOrder'])->name('order.create');
                Route::prefix('ajax')->group(function (){
                    Route::post('calculate',[OrderController::class,'calculate'])->name('order.ajax.calculate');
                });
            });

            Route::prefix('article')->group(function () {
                Route::get('list', [ArticleController::class, 'list'])->name('article.list'); // لیست مقالات
            });

            Route::prefix('profit-manager')->group(function () {
                Route::get('list', [ProfitManagerController::class, 'list'])->name('profit-manager.list'); // لیست مقالات
            });


            Route::prefix('type')->group(function () {
                Route::get('list', [TypeController::class, 'index'])->name('type.list'); // لیست نوع‌ها
                Route::post('/store', [TypeController::class, 'store'])->name('type.store'); // ذخیره نوع
            });
            Route::get('/print-hp-invoice/{order}/', [PrinterController::class, 'printHPInvoice'])->name('print.hp.invoice');
            Route::prefix('user')->group(function () {
                Route::middleware('auth:sanctum')->get('/', function (Request $request) {
                    return (new Response())->ApiResponse([
                        'message' => 'عملیات موفقیت‌آمیز بود',
                        User::query()->with(['profitManager', 'roles', 'roles.permissions'])->find($request->user()->id),
                    ]);
                })->name('user.profile');
                Route::get('list', [UserController::class, 'list'])->name('user.list'); // لیست کاربران
                Route::get('guest', [UserController::class, 'guestList'])->name('user.guest-list'); // لیست کاربران مهمان
            });
        });

        Route::middleware('role:cashier|admin')->group(function () {
            Route::get('customers/list',[CustomerController::class, 'list'])->name('customers.list');
            Route::prefix('order')->group(function () {
                Route::put('update/{order}', [OrderController::class, 'update'])->name('order.update'); // بروزرسانی سفارش
                Route::delete('delete/{order}', [OrderController::class, 'destroy'])->name('order.destroy'); // حذف سفارش
                Route::post('complete/{order}', [OrderController::class, 'orderComplete'])->name('order.complete'); // تکمیل سفارش
                Route::post('pre-invoice/{order}', [OrderController::class, 'preInvoice'])->name('order.pre-invoice'); // تکمیل سفارش
            });
            Route::prefix('printer')->group(function () {
                Route::get('/list', [PrinterController::class, 'list'])->name('printer.list'); // لیست پرینترها
            });
            Route::prefix('discount')->group(function () {
                Route::get('/global', [DiscountController::class, 'indexGlobal'])->name('discount.index.global'); // ایجاد تخفیف
                Route::get('/list', [DiscountController::class, 'list'])->name('discount.list'); // ایجاد تخفیف
            });
        });
        // روت‌های مدیر
        Route::middleware('role:admin')->group(function () {
            Route::prefix('food')->group(function () {
                Route::post('create', [FoodController::class, 'create'])->name('food.create');
            });
            Route::post('/sms/send-bulk', [SmsController::class, 'sendBulk']);
            Route::post('/sms/send-single', [SmsController::class, 'sendSingle']);
            Route::prefix('profit-manager')->group(function () {
                Route::post('create', [ProfitManagerController::class, 'create'])->name('profit-manager.create');
                Route::put('update/{profitManager}', [ProfitManagerController::class, 'update'])->name('profit-manager.update');
            });
            Route::prefix('article')->group(function () {
                Route::post('create', [ArticleController::class, 'create'])->name('article.create');
                Route::put('update/{article}', [ArticleController::class, 'update'])->name('article.update');
            });
            Route::prefix('role')->group(function () {
                Route::get('list', [UserController::class, 'roleList'])->name('role.list'); // لیست نقش‌ها
                Route::post('assign', [UserController::class, 'assign'])->name('role.assign'); // اختصاص نقش‌ها
            });

            Route::prefix('printer')->group(function () {
                Route::post('/create', [PrinterController::class, 'store'])->name('printer.create'); // افزودن پرینتر
                Route::put('update/{printer}', [PrinterController::class, 'update'])->name('printer.update'); // بروزرسانی پرینتر
            });

            Route::prefix('discount')->group(function () {
                Route::post('/store', [DiscountController::class, 'store'])->name('discount.store'); // ایجاد تخفیف
                Route::get('/update-status/{discount}', [DiscountController::class, 'updateStatus'])->name('discount.updateStatus'); // ایجاد تخفیف
                Route::post('/store/global', [DiscountController::class, 'storeGlobal'])->name('discount.store.global'); // ایجاد تخفیف
                Route::delete('/destroy/{id}', [DiscountController::class, 'destroy'])->name('discount.destroy'); // حذف تخفیف
            });

            Route::prefix('club-setting')->group(function () {
                Route::get('/', [ClubSettingController::class, 'index'])->name('club-setting.index');
                Route::post('/', [ClubSettingController::class, 'create'])->name('club-setting.create');
            });
            Route::prefix('next-purchase-discount')->group(function () {
                Route::get('/', [NextPurchaseDiscountController::class, 'index'])->name('next-purchase-discount.index');
                Route::post('/', [NextPurchaseDiscountController::class, 'store'])->name('next-purchase-discount.create');
                Route::put('/{id}', [NextPurchaseDiscountController::class, 'update'])->name('next-purchase-discount.update');
                Route::delete('/{id}', [NextPurchaseDiscountController::class, 'destroy'])->name('next-purchase-discount.destroy');
                Route::get('/active', [NextPurchaseDiscountController::class, 'getActiveDiscounts'])->name('next-purchase-discount.active');
            });
            Route::prefix('reports')->group(function () {
                Route::get('customers', [ReportController::class, 'customers'])->name('reports.customers');
                Route::get('resident-customers', [ReportController::class, 'residentCustomers'])->name('reports.resident-customers');
                Route::get('discount-used-global', [ReportController::class, 'discountUsedGlobal'])->name('reports.discount-used-global');
                Route::get('discount-used-normal', [ReportController::class, 'discountUsedNormal'])->name('reports.discount-used-normal');
            });
            Route::get('setting', [SettingController::class, 'index'])->name('setting.index');
            Route::put('setting/update/{setting}', [SettingController::class, 'update'])->name('setting.update'); // تنظیمات
            Route::prefix('log')->group(function () {
                Route::get('/', [LogController::class, 'list'])->name('log.list'); // لیست لاگ‌ها
            });
        });

// garson casheir finance
        Route::middleware('role:admin|garson|cashier|finance')->group(function () {
            Route::prefix('food')->group(function () {
                Route::get('list', [FoodController::class, 'list'])->name('food.list'); // لیست غذاها
            });
        });

        Route::prefix('discount')->group(function () {
            Route::get('/valid', [DiscountController::class, 'listValid'])->name('discount.valid');
        });

        Route::middleware('role:admin|cashier|finance|cost_control')->group(function () {
            Route::get('order/list', [OrderController::class, 'list'])->name('order.list'); // لیست سفارشات

        });


        Route::middleware('role:admin|finance|cost_control')->group(function () {
            Route::get('food/reporting', [FoodController::class, 'reporting'])->name('food.reporting'); // لیست سفارشات

            Route::put('food/update/{food}', [FoodController::class, 'update'])->name('food.update');
        });
    });
});
