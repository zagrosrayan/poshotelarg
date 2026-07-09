<?php

namespace App\Http\Controllers;

use App\Http\Service\TypeSlug;
use App\Models\Log;
use App\Models\Printer;
use App\Models\Type;
use App\Models\Order;
use App\Service\Response;
use App\Service\PrintService;
use App\Service\validateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PrinterController extends Controller
{
    // Add a printer
    public function store(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|exists:types,id',
            'type' => 'nullable|exists:types,id',
            'article_id' => 'nullable|exists:articles,id',
            'profit_manager_id' => 'nullable|exists:profit_managers,id',
            'food_id' => 'nullable|exists:food,id',
        ]);
        if ($validationResult !== true) {
            return $validationResult;
        }
        try {
            $printer = Printer::create([
                'name' => $request->name,
                'location' => $request->location,
                'type' => $request->type,
                'article_id' => $request->article_id,
                'profit_manager_id' => $request->profit_manager_id,
                'food_id' => $request->food_id,
                'status' => $request->status,
            ]);
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Printer::class,
                'loggable_id' => $printer->id,
                'message' => 'عملیات ایجاد پرینتر با موفقیت انجام شد',
                'date' => now(),
                'status' =>   Type::query()->where('slug',TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);
            return (new Response())->ApiResponse([
                'message' => 'عملیات با موفقیت انجام شد.',
                'items'=> Printer::query()->where('id',$printer->id)->with(['article','profitManager','food','type'])->first()
            ]);
        }catch (\Exception $exception){
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Printer::class,
                'loggable_id' => null,
                'message' => 'عملیات ایجاد پرینتر با با خطا مواجه شد',
                'date' => now(),
                'status' =>   Type::query()->where('slug',TypeSlug::LOG_STATUS_FAILED)->first()->id
            ]);
            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ]);
        }
    }

    public function update(Request $request, Printer $printer)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|exists:types,id',
            'type' => 'nullable|exists:types,id',
            'article_id' => 'nullable|exists:articles,id',
            'profit_manager_id' => 'nullable|exists:profit_managers,id',
            'food_id' => 'nullable|exists:food,id',
        ]);
        if ($validationResult !== true) {
            return $validationResult;
        }
        try {

            $printer->update($request->all());
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_UPDATE)->first()->id,
                'loggable_type' => Printer::class,
                'loggable_id' => $printer->id,
                'message' => 'عملیات ویرایش پرینتر با موفقیت انجام شد',
                'date' => now(),
                'status' =>   Type::query()->where('slug',TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);
            return (new Response())->ApiResponse([
                'message' => 'عملیات با موفقیت انجام شد.',
                'items'=> Printer::query()->find($printer->id)->with(['article','profitManager','food','type'])->first()
            ]);
        }catch (\Exception $exception){
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_UPDATE)->first()->id,
                'loggable_type' => Printer::class,
                'loggable_id' => null,
                'message' => 'عملیات ویرایش پرینتر با با خطا مواجه شد',
                'date' => now(),
                'status' =>   Type::query()->where('slug',TypeSlug::LOG_STATUS_FAILED)->first()->id
            ]);
            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ]);
        }
        // Check if the IP is reachable
    }

    public function list(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|exists:types,id',
            'type' => 'nullable|exists:types,id',
            'article_id' => 'nullable|exists:articles,id',
            'profit_manager_id' => 'nullable|exists:profit_managers,id',
            'food_id' => 'nullable|exists:food,id',
        ]);
        if ($validationResult !== true) {
            return $validationResult;
        }
        try {
            $printers = Printer::query()
                ->when($request->name, function ($query, $name) {
                    $query->where('name', 'like', "%{$name}%");
                })
                ->when($request->location, function ($query, $location) {
                    $query->where('location', 'like', "%{$location}%");
                })
                ->when($request->ip, function ($query, $ip) {
                    $query->where('ip', $ip);
                })
                ->when($request->status, function ($query, $status) {
                    $query->where('status', $status);
                })
                ->when($request->type, function ($query, $type) {
                    $query->where('type', $type);
                })
                ->when($request->article_id, function ($query, $article_id) {
                    $query->where('article_id', $article_id);
                })
                ->when($request->profit_manager_id, function ($query, $profit_manager_id) {
                    $query->where('profit_manager_id', $profit_manager_id);
                })
                ->when($request->food_id, function ($query, $food_id) {
                    $query->where('food_id', $food_id);
                })
            ;
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Printer::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست پرینتر با موفقیت انجام شد',
                'date' => now(),
                'status' =>   Type::query()->where('slug',TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);
            return (new Response())->ApiPaginatedResponse(
                $printers->with(['article','profitManager','food','type'])->paginate(10)
            );
        }catch (\Exception $exception){
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Printer::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست پرینتر با با خطا مواجه شد',
                'date' => now(),
                'status' =>   Type::query()->where('slug',TypeSlug::LOG_STATUS_FAILED)->first()->id
            ]);
            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ]);
        }
    }

    public function printHPInvoice(Order $order)
    {
        try {
            // چک کردن وضعیت سفارش
            if (!$order || !$order->exists) {
                return (new Response())->ApiResponse([
                    'status' => 400,
                    'message' => 'سفارش یافت نشد'
                ]);
            }

            // گروه‌بندی آیتم‌ها بر اساس پرینتر
            $itemsGroupedByPrinter = [];
            foreach ($order->children as $item) {
                $printer = $this->getPrinterForItem($item);
                if ($printer) {
                    $itemsGroupedByPrinter[$printer->id][] = $item;
                }
            }

            if (empty($itemsGroupedByPrinter)) {
                return (new Response())->ApiResponse([
                    'status' => 400,
                    'message' => 'هیچ پرینتری برای این سفارش یافت نشد'
                ]);
            }

            $printService = (new PrintService);
            foreach ($itemsGroupedByPrinter as $printerId => $items) {
                $printer = Printer::find($printerId);
                if ($printer) {
                    $order->children = collect($items);
                    if ($printer->type == Type::query()->where('slug',TypeSlug::LASER_PRINTER)->first()->id) {
                        $printService->printHPInvoice($order, $printer->name);
                    }else{
                        return (new Response())->ApiResponse([
                            'status' => 400,
                            'message' => 'هیچ پرینتری برای این سفارش یافت نشد'
                        ]);
                    }
                }
            }

            return (new Response())->ApiResponse([
                'status' => 200,
                'message' => 'فاکتور با موفقیت به پرینترهای مربوطه ارسال شد'
            ]);

        } catch (\Exception $e) {
            return (new Response())->ApiResponse([
                'status' => 400,
                'message' => 'خطا در چاپ فاکتور: ' . $e->getMessage()
            ]);
        }
    }

    private function getPrinterForItem($item)
    {
        $type = Type::query()->where('slug',TypeSlug::LASER_PRINTER)->first();
        // Priority 1: Food specific printer
        if ($item->food_id) {
            $printer = Printer::where('food_id', $item->food_id)->where('type', $type->id)->first();
            if ($printer) {
                return $printer;
            }
        }

        // Priority 2: Profit Manager printer
        if ($item->food && $item->food->profit_manager_id) {
            $printer = Printer::where('profit_manager_id', $item->food->profit_manager_id)
                ->where('type', $type->id)->first();
            if ($printer) {
                return $printer;
            }
        }

        // Priority 3: Article (category) printer
        if ($item->food && $item->food->article_id) {
            $printer = Printer::where('article_id', $item->food->article_id)->where('type', $type->id)->first();
            if ($printer) {
                return $printer;
            }
        }

        return null;
    }
}
