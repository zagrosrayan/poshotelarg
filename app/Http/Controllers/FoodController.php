<?php

namespace App\Http\Controllers;

use App\Http\Service\TypeSlug;
use App\Models\Article;
use App\Models\Food;
use App\Models\Log;
use Mpdf\Mpdf;

use App\Models\Order;
use App\Models\ProfitManager;
use App\Models\Type;
use App\Service\Response;
use App\Service\validateRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FoodController extends Controller
{
    public function list(Request $request)
    {

        $validationResult = (new validateRequest())->validate($request->all(), [
            'name'                 => 'nullable|string',
            'slug'                 => 'nullable|string',
//            'article_id'           => 'nullable|exists:articles,id',
//            'profit_manager_id'    => 'nullable|exists:profit_managers,id',
        ]);
        if ($validationResult !== true) {
            return $validationResult;
        }
        try {
            $profit_manager_bakery = ProfitManager::query()
                ->where('slug', TypeSlug::PROFIT_MANAGER_TYPE_BAKERY)
                ->first()->id;

            $profit_manager_restaurant = ProfitManager::query()
                ->where('slug', TypeSlug::PROFIT_MANAGER_TYPE_RESTAURANT)
                ->first()->id;

            $food = Food::query()->with(['article', 'profitManager'])
                ->when($request->name, function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->name . '%');
                })->when($request->slug, function ($query) use ($request) {
                    $query->where('slug', $request->slug);
                })->when($request->article_id, function ($query) use ($request) {
                    $query->where('article_id', $request->article_id);
                })->when($request->profit_manager_id, function ($query) use ($request) {
                    $query->where('profit_manager_id', $request->profit_manager_id);
                })
                ->when($request->user() && $request->user()->profit_manager_id, function ($query) use ($request, $profit_manager_bakery, $profit_manager_restaurant) {
                    if ($request->user()->hasRole('admin')) {
                        return;
                    }
                    if ($request->user()->profit_manager_id == $profit_manager_bakery) {
                        return;
                    }
                    if ($request->user()->profit_manager_id == $profit_manager_restaurant) {
                        $query->whereIn('profit_manager_id', [$request->user()->profit_manager_id, $profit_manager_bakery]);
                    } else {
                        $query->where('profit_manager_id', $request->user()->profit_manager_id);
                    }
                })->paginate(10);
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Food::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست غذاها با موفقیت انجام شد',
                'date' => now(),
                'status' =>   Type::query()->where('slug',TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);
            return (new Response())->ApiPaginatedResponse(
                $food
            );
        }catch (\Exception $exception){
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Food::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست غذاها با خطا مواجه شد',
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

    public function create(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name'                 => 'required|string',
            'slug'                 => 'required|string|unique:food,slug',
            'price'                => 'required|numeric',
            'description'          => 'required|string',
            'article_id'           => 'required|string|exists:articles,id',
            'profit_manager_id'    => 'required|string|exists:profit_managers,id',
        ]);
        if ($validationResult !== true) {
            return $validationResult;
        }
        try {
            $food = Food::query()->create([
                'name' => $request->name,
                'price' => $request->price,
                'description' => $request->description,
                'article_id' => $request->article_id,
                'slug' => $request->slug,

                'profit_manager_id' => $request->profit_manager_id,
            ]);
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Food::class,
                'loggable_id' => $food->id,
                'message' => 'عملیات ایجاد غذاها با موفقیت انجام شد',
                'date' => now(),
                'status' =>   Type::query()->where('slug',TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);
            return (new Response())->ApiResponse(
                [
                    $food,
                    'message' => 'عملیات موفقیت امیز بود'
                ]
            );
        }catch (\Exception $exception){
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Food::class,
                'loggable_id' => null,
                'message' => 'عملیات  ایجاد غذاها با خطا مواجه شد',
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

    public function update(Request $request, Food $food)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name'                 => 'nullable|string',
            'slug'                 => 'nullable|string|unique:food,slug,' . $food->id,
            'price'                => 'nullable|numeric',
            'description'          => 'nullable|string',
            'article_id'           => 'nullable|string|exists:articles,id',
            'profit_manager_id'    => 'nullable|string|exists:profit_managers,id',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $food->update([
                'name' => $request->name,
                'slug' => $request->slug,
                'price' => $request->price,
                'description' => $request->description,
                'article_id' => $request->article_id,
                'profit_manager_id' => $request->profit_manager_id,
            ]);

            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_UPDATE)->first()->id,
                'loggable_type' => Food::class,
                'loggable_id' => $food->id,
                'message' => 'عملیات بروزرسانی غذا با موفقیت انجام شد',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);

            return (new Response())->ApiResponse([
                $food,
                'message' => 'عملیات بروزرسانی موفقیت آمیز بود'
            ]);
        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_UPDATE)->first()->id,
                'loggable_type' => Food::class,
                'loggable_id' => null,
                'message' => 'عملیات بروزرسانی غذا با خطا مواجه شد',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_FAILED)->first()->id
            ]);

            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ]);
        }
    }

    public function reporting(Request $request)
    {
        $query = Food::query();

        $query->when($request->profit_manager_id, function ($q) use ($request) {
            $q->where('profit_manager_id', $request->profit_manager_id);
        });

        $query->when($request->food_id, function ($q) use ($request) {
            $q->where('id', $request->food_id);
        });

        $query->when($request->article_id, function ($q) use ($request) {
            $q->where('article_id', $request->article_id);
        });


        $query->whereHas('orders', function ($orderQuery) use ($request) {
            $orderQuery->whereNotNull('food_id');

            if ($request->from) {
                $orderQuery->whereDate('created_at', '>=', $request->from);
            }
            if ($request->to) {
                $orderQuery->whereDate('created_at', '<=', $request->to);
            }
        });

        $query->with([
            'orders' => function ($q) use ($request) {
                $q->whereNotNull('food_id')
                    ->select('id', 'food_id', 'invoice_number', 'parent_id', 'quantity', 'price', 'tax', 'rate_service', 'discounted_price', 'created_at');

                if ($request->from) {
                    $q->whereDate('created_at', '>=', $request->from);
                }
                if ($request->to) {
                    $q->whereDate('created_at', '<=', $request->to);
                }
                if ($request->invoice_number) {
                    $q->where('invoice_number', 'like','%'. $request->invoice_number.'%');
                }
                $q->orderBy('created_at', 'desc');
            },
            'orders.parent:id,invoice_number',
            'orders.food:id,price',
            'article:id,name',
            'profitManager:id,name'
        ]);

        $query->orderBy('created_at', 'desc');

        $transformFood = function ($food) {
            $totalQuantity = 0;
            $totalOrderPrice = 0;
            $totalTax = 0;
            $totalService = 0;
            $totalDiscount = 0;
            $totalFinal = 0;

            $orders = $food->orders->map(function ($order) use (&$totalQuantity, &$totalOrderPrice, &$totalTax, &$totalService, &$totalDiscount, &$totalFinal) {
                $foodPrice = $order->food ? $order->food->price : 0;
                $orderPrice = $order->price;
                $quantity = $order->quantity;

                if ($foodPrice > 0) {
                    if (abs($orderPrice - $foodPrice) / $foodPrice < 0.01) {
                        $unitPrice = $orderPrice;
                    } else if ($quantity > 0 && abs(($orderPrice / $quantity) - $foodPrice) / $foodPrice < 0.01) {
                        $unitPrice = $foodPrice;
                    } else {
                        $unitPrice = $foodPrice;
                    }
                } else {
                    $unitPrice = $quantity > 0 ? $orderPrice / $quantity : $orderPrice;
                }

                $totalPrice = $quantity * $unitPrice;
                $total = $totalPrice + ($order->tax ?? 0) + ($order->rate_service ?? 0) - ($order->discounted_price ?? 0);

                $totalQuantity += $quantity;
                $totalOrderPrice += $totalPrice;
                $totalTax += ($order->tax ?? 0);
                $totalService += ($order->rate_service ?? 0);
                $totalDiscount += ($order->discounted_price ?? 0);
                $totalFinal += $total;

                return [
                    'invoice_number'    => $order->parent ? $order->parent->invoice_number : $order->invoice_number,
                    'quantity'          => $quantity,
                    'tax'               => $order->tax,
                    'rate_service'      => $order->rate_service,
                    'price'             => $unitPrice,
                    'total_price'       => $totalPrice,
                    'discounted_price'  => $order->discounted_price,
                    'total'             => $total,
                    'created_at'        => $order->created_at->format('Y-m-d H:i:s'),
                ];
            });

            $sumUnitPrices = $orders->sum(function($order) {
                return $order['price'] * $order['quantity'];
            });

            $averagePrice = $totalQuantity > 0
                ? round($sumUnitPrices / $totalQuantity)
                : 0;

            return [
                'id'             => $food->id,
                'food_name'      => $food->name,
                'article'        => $food->article?->name,
                'profit_manager' => $food->profitManager?->name,
                'summary'        => [
                    'total_quantity' => $totalQuantity,
                    'total_price'    => $totalFinal,
                    'average_price'  => $averagePrice,
                    'order_count'    => $orders->count(),
                ],
                'total_summary'  => [
                    'total_quantity'      => $totalQuantity,
                    'total_order_price'   => $totalOrderPrice,
                    'total_tax'           => $totalTax,
                    'total_service'       => $totalService,
                    'total_discount'      => $totalDiscount,
                    'total_final'         => $totalFinal,
                ],
                'orders'         => $orders->toArray(),
            ];
        };

        if ($request->excel == true) {
            ini_set('max_execution_time', 600);
            ini_set('memory_limit', '1024M');

            $foods = $query->get()->map($transformFood);

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\FoodReportExport($foods),
                'foods_report_' . time() . '.xlsx'
            );
        }

        if ($request->pdf == true) {
            ini_set('memory_limit', '1024M');
            ini_set('pcre.backtrack_limit', '5000000');
            set_time_limit(600);

            $foods = $query->get()->map($transformFood);

            $mpdf = new \Mpdf\Mpdf([
                'mode'         => 'utf-8',
                'format'       => 'A4-L',
                'default_font' => 'Vazir',
                'tempDir'      => storage_path('app/temp'),
            ]);

            $mpdf->simpleTables = true;
            $mpdf->packTableData = true;

            $headerHtml = view('food_pdf_header')->render();
            $mpdf->WriteHTML($headerHtml);

            $chunks = $foods->chunk(5);

            foreach ($chunks as $index => $chunk) {
                $html = view('food_pdf_chunk', ['foods' => $chunk])->render();
                $mpdf->WriteHTML($html);
            }

            $filePath = storage_path('app/public/foods_' . time() . '.pdf');
            $mpdf->Output($filePath, 'F');

            return response()->download($filePath)->deleteFileAfterSend(true);
        }

        $perPage = $request->per_page ?? 15;
        $foods = $query->paginate($perPage);
        $foods->getCollection()->transform($transformFood);

        return (new Response())->ApiPaginatedResponse($foods);
    }
}
