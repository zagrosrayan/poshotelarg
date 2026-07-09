<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\ProfitManagerController;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\UserController;
use App\Models\User;
use App\Service\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Storage;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;
use Spatie\Browsershot\Browsershot;

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



Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->name('auth.login'); // ورود
    });

    Route::middleware('auth:sanctum')->group(function () {
        // روت‌های گارسون
        Route::middleware('role:garson')->group(function () {
            Route::prefix('order')->group(function () {
                Route::post('create/guest', [OrderController::class, 'createGuest'])->name('order.create-guest'); // ایجاد سفارش مهمان
                Route::post('create/resident', [OrderController::class, 'createResident'])->name('order.create-resident'); // ایجاد سفارش مقیم
            });

            Route::prefix('article')->group(function () {
                Route::get('list', [ArticleController::class, 'list'])->name('article.list'); // لیست مقالات
            });

            Route::prefix('profit-manager')->group(function () {
                Route::get('list', [ProfitManagerController::class, 'list'])->name('profit-manager.list'); // لیست مقالات
            });

            Route::prefix('food')->group(function () {
                Route::get('list', [FoodController::class, 'list'])->name('food.list'); // لیست غذاها
            });
            Route::prefix('type')->group(function () {
                Route::get('list', [TypeController::class, 'index'])->name('type.list'); // لیست نوع‌ها
                Route::post('/store', [TypeController::class, 'store'])->name('type.store'); // ذخیره نوع
            });

                Route::prefix('user')->group(function () {
                    Route::middleware('auth:sanctum')->get('/', function (Request $request) {
                        return (new Response())->ApiResponse([
                            'message' => 'عملیات موفقیت‌آمیز بود',
                            User::query()->with(['profitManager','roles','roles.permissions'])->find($request->user()->id),
                        ]);
                    })->name('user.profile');
                Route::get('list', [UserController::class, 'list'])->name('user.list'); // لیست کاربران
                Route::get('guest', [UserController::class, 'guestList'])->name('user.guest-list'); // لیست کاربران مهمان
            });
        });


        // روت‌های مدیر
        Route::middleware('role:admin')->group(function () {
            Route::prefix('order')->group(function () {
                Route::get('list', [OrderController::class, 'list'])->name('order.list'); // لیست سفارشات
                Route::put('update/{order}', [OrderController::class, 'update'])->name('order.update'); // بروزرسانی سفارش
                Route::delete('delete/{order}', [OrderController::class, 'destroy'])->name('order.destroy'); // حذف سفارش
                Route::get('complete/{order}', [OrderController::class, 'completeOrder'])->name('order.complete'); // تکمیل سفارش
            });

            Route::prefix('role')->group(function () {
                Route::get('list', [UserController::class, 'roleList'])->name('role.list'); // لیست نقش‌ها
                Route::post('assign', [UserController::class, 'assign'])->name('role.assign'); // اختصاص نقش‌ها
            });

            Route::prefix('printer')->group(function () {
                Route::post('/create', [PrinterController::class, 'store'])->name('printer.create'); // افزودن پرینتر
                Route::get('/list', [PrinterController::class, 'list'])->name('printer.list'); // لیست پرینترها
                Route::put('update/{printer}', [PrinterController::class, 'update'])->name('printer.update'); // بروزرسانی پرینتر
            });

            Route::prefix('discount')->group(function () {
                Route::post('/store', [DiscountController::class, 'store'])->name('discount.store'); // ایجاد تخفیف
                Route::get('/list', [DiscountController::class, 'list'])->name('discount.list'); // ایجاد تخفیف
                Route::delete('/destroy/{id}', [DiscountController::class, 'destroy'])->name('discount.destroy'); // حذف تخفیف
            });

            Route::put('setting/update/{setting}', [\App\Http\Controllers\SettingController::class, 'update'])->name('setting.update'); // تنظیمات
            Route::prefix('log')->group(function () {
                Route::get('/', [LogController::class, 'list'])->name('log.list'); // لیست لاگ‌ها
            });
        });
    });
});

Route::get('/print', function () {
    try {
        // مسیر فایل تصویر
//        $imagePath = storage_path('app/public/images.png'); // مسیر فایل تصویر

        // مسیر پرینتر IP و پورت
        $printerIp = '192.168.10.199'; // IP پرینتر
        $printerPort = 9100; // پورت پرینتر (RAW)

        // اتصال به پرینتر
        $connector = new NetworkPrintConnector($printerIp, $printerPort);
        $printer = new Printer($connector);
        // ارسال تصویر به پرینتر
        $printer->text("سلام این یک تست است                   \n",);
        $printer->

        // قطع کاغذ
        $printer->cut();

        // بستن ارتباط
        $printer->close();

        return response()->json(['status' => 'Image sent to printer successfully!']);
    } catch (\Exception $e) {
        return response()->json(['status' => 'Error: ' . $e->getMessage()], 500);
    }
});


Route::get('/print-pdf', function () {
    $printerIp = '192.168.10.199'; // IP پرینتر
    $printerPort = 9100; // پورت پرینتر (RAW)

    try {
        $htmlContent = '<html><body><h1>سلام این یک تست است</h1></body></html>';

        // تبدیل HTML به PDF
        $pdf = PDF::loadHTML($htmlContent);
        $pdfDirectory = storage_path('app/public/pdf');
        $pdfPath = $pdfDirectory . '/html_pdf_' . time() . '.pdf';

        // ایجاد دایرکتوری در صورت عدم وجود
        if (!File::exists($pdfDirectory)) {
            File::makeDirectory($pdfDirectory, 0755, true);
        }

        $pdf->save($pdfPath);

        $result = sendToPrinter($printerIp, $printerPort, $pdfPath);

        // حذف PDF بعد از پرینت
        Storage::delete('public/pdf/' . basename($pdfPath));

        if ($result) {
            return response()->json(['message' => 'PDF با موفقیت ایجاد و به پرینتر ارسال شد!']);
        } else {
            return response()->json(['message' => 'خطا در ارسال به پرینتر!'], 500);
        }
    } catch (\Exception $e) {
        return response()->json(['status' => 'Error: ' . $e->getMessage()], 500);
    }
});

// تابع برای ارسال PDF به پرینتر
function sendToPrinter($printerIp, $printerPort, $filePath)
{
    try {
        // اتصال به پرینتر
        $connector = new NetworkPrintConnector($printerIp, $printerPort);
        $printer = new Printer($connector);

        // خواندن محتوای فایل PDF
        $fileData = file_get_contents($filePath);

        // ارسال داده‌های PDF به پرینتر
        $printer->text($fileData);
        $printer->cut();

        // بستن ارتباط
        $printer->close();

        return true;
    } catch (\Exception $e) {
        // بستن ارتباط در صورت بروز خطا
        if (isset($printer)) {
            $printer->close();
        }
        return false;
    }
}
