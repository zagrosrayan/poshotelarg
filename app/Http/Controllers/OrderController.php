<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log as Logger;
use App\Http\Requests\CompleteOrderRequest;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Service\TypeSlug;
use App\Jobs\SendNextPurchaseDiscountToCustomers;
use App\Jobs\SendOrderCompleteSms;
use App\Models\Article;
use App\Models\ClubSetting;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Food;
use App\Models\GuestUser;
use App\Models\Log;
use App\Models\NextPurchaseDiscount;
use App\Models\Order;
use App\Models\ProfitManager;
use App\Models\Printer;
use App\Models\ResidentCustomerPoint;
use App\Models\Setting;
use App\Models\Type;
use App\Models\User;
use App\Repository\OrderRepository;
use App\Rules\CheckDiscountValid;
use App\Service\DiscountService;
use App\Service\PrintService;
use App\Service\Response;
use App\Service\validateRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Rules\DescNumberRule;

use Illuminate\Support\Facades\Validator;
use Morilog\Jalali\Jalalian;
use phpDocumentor\GraphViz\Exception;

class OrderController extends Controller
{

    public function orderReporting(Request $request)
    {
        try {
            $request->validate([
                'from' => 'nullable|date|before_or_equal:to',
                'to' => 'nullable|date|after_or_equal:from',
                'room_number' => 'nullable|integer|min:1',
                'food_id' => 'nullable|exists:foods,id',
                'invoice_number' => 'nullable',
                'profit_manager_id' => 'nullable|exists:profit_managers,id',
                'pdf' => 'nullable|boolean',
            ]);
            $profit_manager_bakery = ProfitManager::query()
                ->where('slug', TypeSlug::PROFIT_MANAGER_TYPE_BAKERY)
                ->first()->id;

            $profit_manager_restaurant = ProfitManager::query()
                ->where('slug', TypeSlug::PROFIT_MANAGER_TYPE_RESTAURANT)
                ->first()->id;

            $ordersQuery = Order::query()->whereNull('parent_id')->with([
                'customer',
                'reserve',
                'user',
                'user.profitManager',
                'status',
                'paymentMethod',
                'discount',
                'food',
                'parent',
                'children.food',
                'children.status',
            ])
                ->when($request->from, function ($query) use ($request) {
                    $query->whereDate('created_at', '>=', $request->from);
                })
                ->when($request->to, function ($query) use ($request) {
                    $query->whereDate('created_at', '<=', $request->to);
                })
                ->when($request->invoice_number, function ($query) use ($request) {
                    $query->whereDate('invoice_number', $request->invoice_number);
                })
                ->when($request->user() && $request->user()->profit_manager_id, function ($query) use ($request, $profit_manager_bakery, $profit_manager_restaurant) {
                    if ($request->user()->hasRole('admin')) {
                        return;
                    }
                    if ($request->user()->profit_manager_id == $profit_manager_bakery) {
                        return;
                    }
                    $query->whereHas('children.food', function ($query) use ($request, $profit_manager_bakery, $profit_manager_restaurant) {
                        if ($request->user()->profit_manager_id == $profit_manager_restaurant) {
                            $query->whereIn('profit_manager_id', [$request->user()->profit_manager_id, $profit_manager_bakery]);
                        } else {
                            $query->where('profit_manager_id', $request->user()->profit_manager_id);
                        }
                    });
                })
                ->when($request->room_number, function ($query) use ($request) {
                    $query->whereHas('reserve', function ($q) use ($request) {
                        $q->where('Room', $request->room_number);
                    });
                })
                ->when($request->food_id, function ($query) use ($request) {
                    $query->whereHas('children.food', function ($query) use ($request) {
                        $query->where('id',$request->food_id);
                    });
                })
                ->when($request->profit_manager_id,function ($query) use ($request){
                    $query->whereHas('children.food.profitManager', function ($query) use ($request) {
                        $query->where('id',$request->profit_manager_id);
                    });
                })
                ->orderBy('created_at', 'desc');

            $orders = $ordersQuery->get();

            $averageTotalPrice = (clone $ordersQuery)->avg('total_price');
            if ($request->pdf == true) {
                $pdf = Pdf::loadView('orders_pdf', [
                    'orders' => $orders,
                    'averageTotalPrice' => $averageTotalPrice,
                ]);
                $filePath = storage_path('app/public/orders_report.pdf');
                $pdf->save($filePath);
                return response()->download($filePath);            }
            return (new Response())->ApiPaginatedResponse(
                $ordersQuery->paginate(),
                extraFields: [
                    'average_total_price' => $averageTotalPrice,
                ]
            );
        }catch (\Exception $exception){
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Order::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست سفارش با خطا مواجه شد',
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
    public function list(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'today' => 'nullable|in:0,1',
            'from' => 'nullable|string',
            'to' => 'nullable|string',
            'room_number' => 'nullable|string',
            'order_id' => 'nullable|string',
            'invoice_number' => 'nullable|string',
        ]);
        if ($validationResult !== true) {
            return $validationResult;
        }
        try {
            $profit_manager_bakery = ProfitManager::query()
                ->where('slug', TypeSlug::PROFIT_MANAGER_TYPE_BAKERY)
                ->first()->id;

            $profit_manager_restaurant = ProfitManager::query()
                ->where('slug', TypeSlug::PROFIT_MANAGER_TYPE_RESTAURANT) // فرض بر این است که slug رستوران تعریف شده
                ->first()->id;

            $orders = Order::query()->whereNull('parent_id')->with([
                'customer',
                'reserve',
                'user',
                'user.profitManager',
                'status',
                'paymentMethod',
                'discount',
                'food',
                'parent',
                'children.food',
                'children.status',
                'nextPurchaseDiscount'
            ])->when($request->today == 1, function ($query) use ($request) {
                $query->whereDate('created_at', today());
            })
                ->when($request->from, function ($query) use ($request) {
                    $query->whereDate('created_at', '>=', $request->from);
                })
                ->when($request->to, function ($query) use ($request) {
                    $query->whereDate('created_at', '<=', $request->to);
                })
                ->when($request->order_id, function ($query) use ($request) {
                    $query->where('id', $request->order_id);
                })
                ->when($request->invoice_number, function ($query) use ($request) {
                    $query->where('invoice_number', 'like','%' . $request->invoice_number.'%');
                })
                ->when($request->user() && $request->user()->profit_manager_id, function ($query) use ($request, $profit_manager_bakery, $profit_manager_restaurant) {
                    if ($request->user()->hasRole('admin')) {
                        return;
                    }
                    if ($request->user()->profit_manager_id == $profit_manager_bakery) {
                        return;
                    }
                    $query->whereHas('children.food', function ($query) use ($request, $profit_manager_bakery, $profit_manager_restaurant) {
                        if ($request->user()->profit_manager_id == $profit_manager_restaurant) {
                            $query->whereIn('profit_manager_id', [$request->user()->profit_manager_id, $profit_manager_bakery]);
                        } else {
                            $query->where('profit_manager_id', $request->user()->profit_manager_id);
                        }
                    });
                })
                ->when($request->room_number, function ($query) use ($request) {
                    $query->whereHas('reserve', function ($q) use ($request) {
                        $q->where('Room', $request->room_number);
                    });
                })->orderBy('created_at', 'desc');
            $pendingOrder = $orders->clone()
                ->whereHas('status', function ($query) {
                    $query->where('slug', TypeSlug::ORDER_STATUS_PENDING);
                });

            $pendingOrderCount = $pendingOrder->count();
            $pendingOrderTotal = $pendingOrder->sum('total_price');

            $completeOrder = $orders->clone()
                ->whereHas('status', function ($query) {
                    $query->where('slug', TypeSlug::ORDER_STATUS_COMPLETE);
                });

            $completeOrderCount = $completeOrder->count();
            $completeOrderTotal = $completeOrder->sum('total_price');

            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Order::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست سفارش با موفقیت انجام شد',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);
            return (new Response())->ApiPaginatedResponse(
                $orders->paginate(),
                extraFields: [
                    'pendingOrderCount' => $pendingOrderCount,
                    'pendingOrderTotal' => $pendingOrderTotal,
                    'completeOrderCount' => $completeOrderCount,
                    'completeOrderTotal' => $completeOrderTotal,
                ]
            );
        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Order::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست سفارش با خطا مواجه شد',
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

    public function createOrder(CreateOrderRequest $request)
    {
        try {
            $discountFields = [
                'discount_normal_code' => $request->discount_normal_code,
                'discount_global_code' => $request->discount_global_code,
                'use_next_purchase_discount' => $request->use_next_purchase_discount,
                'discount_value' => $request->discount_value,
                'use_club_points' => $request->use_club_points
            ];

            $activeDiscounts = collect($discountFields)->filter(fn($value) => !empty($value))->count();

            if ($activeDiscounts > 1) {
                return response()->json([
                    'status' => 422,
                    'message' => 'فقط یک نوع تخفیف قابل استفاده است',
                ], 422);
            }

            $discount_code = collect([
                $request->discount_global_code,
                $request->discount_normal_code,
            ])->filter()->first();

            if ($request->boolean('use_next_purchase_discount')) {
                $nextPurchaseDiscount = Discount::where('scope', 'next_purchase')
                    ->where('is_active', true)
                    ->where(function ($q) use ($request) {
                        if ($request->customer_id) {
                            $q->where('customer_id', $request->customer_id);
                        }
                        if ($request->reserve_number) {
                            $q->orWhere('reserve_number', $request->reserve_number);
                        }
                    })
                    ->where('expires_at', '>', now())
                    ->whereColumn('usage_count', '<', 'usage_limit')
                    ->first();

            


                if ($nextPurchaseDiscount) {
                    $discount_code = $nextPurchaseDiscount->code;
                }
            }


        $parentOrder = $this->createBaseOrder(
            $request->order,
            $request->user()->id,
            [
                'rate_service' => $request->rate_service == 1 ? true : false,
                'desc_number' => $request->desc_number,
                'service_type' => $request->service_type ?? null,
                'reserve_number' => $request->reserve_number ?? null,
                'customer_id' => $request->customer_id,
                'customer_mobile' => $request->customer_mobile,
                'customer_name' => $request->customer_name,
                'discount_code' => $discount_code ?? null,
                'discount_value' => $request->discount_value ?? null,
                'discount_type' => $request->discount_type ?? null,
                'use_club_points' => $request->boolean('use_club_points'),
                'is_special' => $request->boolean('is_special'),
            ]
        );

            (new PrintService())->sendOrderToPrinters($parentOrder);

            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Order::class,
                'loggable_id' => $parentOrder->id,
                'message' => 'ثبت سفارش با موفقیت انجام شد',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);

            $response = [
                'message' => 'عملیات موفقیت آمیز بود',
                'items' => Order::with([
                    'customer',
                    'user',
                    'status',
                    'paymentMethod',
                    'discount',
                    'food',
                    'parent',
                    'children.food',
                    'children.status',
                    'reserve'
                ])->find($parentOrder->id)
            ];

            return (new Response())->ApiResponse($response);

        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Order::class,
                'loggable_id' => null,
                'message' => 'ثبت سفارش با خطا مواجه شد',
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
    protected function createBaseOrder(array $orderDetails, int $userId, array $additionalFields = [])
    {
        $total_price = 0;
        foreach ($orderDetails as $order) {
            $food = Food::findOrFail($order['food_id']);
            $total_price += intval(round($food->price * $order['quantity']));
        }

        $order_invoice = Order::query()->latest()->whereNull('parent_id')->first()->invoice_number ?? 0;
        $invoice_number = $order_invoice + 1;
        if (!empty($additionalFields['customer_name']) and !empty($additionalFields['customer_mobile'])) {
           $exists_customer = Customer::query()->where('phone', $additionalFields['customer_mobile'])->first();
           if ($exists_customer) {
               throw new \Exception('کاربر با این شماره موبایل از قبل وجود دارد.');
           }
           $additionalFields['customer_id'] = Customer::query()->create([
                'name' => $additionalFields['customer_name'],
                'phone' => $additionalFields['customer_mobile'],
            ])?->id;
        }
        $orderRepo = app(OrderRepository::class);
        $calculate = $orderRepo->calculatePrice(
            $orderDetails,
            $total_price,
            $additionalFields['discount_code'] ?? null,
            $additionalFields['discount_value'] ?? null,
            $additionalFields['discount_type'] ?? null,
            $additionalFields['rate_service'] ?? false,
            $additionalFields['reserve_number'] ?? null,
            $additionalFields['customer_id'] ?? null,
            $additionalFields['is_special'] ?? false,
            $additionalFields['use_club_points'] ?? false
        );

        $discount = null;

        if (empty($calculate['discount']) && !empty($additionalFields['discount_value'])
            && !empty($additionalFields['discount_type'])) {
            $discount = Discount::query()->create([
                'code' => null,
                'name' => 'تخفیف دستی',
                'discount_value' => $additionalFields['discount_value'],
                'discount_type' => $additionalFields['discount_type'],
                'is_active' => true,
                'scope' => 'in_order'
            ])->id;
        } elseif (!empty($calculate['discount'])) {
            $discount = $calculate['discount'];
        }

        $setting = Setting::first();
        $total_service_fee = 0;
        $total_tax = 0;
        $child_service_fees = [];
        $child_taxes = [];

        foreach ($orderDetails as $order) {
            $food = Food::findOrFail($order['food_id']);
            $item_price = intval(round($food->price * $order['quantity']));
            $item_service_fee = 0;
            $item_price_after_discount = $item_price - intval(round(($item_price / $total_price) * $calculate['discounted_price']));
            if ($additionalFields['rate_service'] == 1) {
                $item_service_fee = intval(round($item_price_after_discount * $setting->rate_service / 100));
            }
            $item_tax = intval(round(($item_price_after_discount + $item_service_fee) * $setting->tax / 100));
            $child_service_fees[] = $item_service_fee;
            $child_taxes[] = $item_tax;
            $total_service_fee += $item_service_fee;
            $total_tax += $item_tax;
        }

        $room_number = null;
        if (!empty($additionalFields['reserve_number'])) {
            $guestUser = GuestUser::query()->where('Reserve', $additionalFields['reserve_number'])->first();
            if ($guestUser) {
                $room_number = $guestUser->Room;
            }
        }

        $orderData = [
            'user_id' => $userId,
            'status' => Type::where('slug', TypeSlug::ORDER_STATUS_PENDING)->first()->id,
            'price' => $calculate['price'],
            'total_price' => $calculate['total_price'],
            'rate_service' => $total_service_fee,
            'tax' => $total_tax,
            'discounted_price' => (int) round($calculate['discounted_price']),
            'discount_id' => $discount,
            'quantity' => array_sum(array_column($orderDetails, 'quantity')),
            'invoice_number' => $invoice_number,
            'service_type' => $additionalFields['service_type'] ?? null,
            'customer_id' => $additionalFields['customer_id'] ?? null,
            'club_points_used' => $calculate['club_points_used'] ?? 0,
            'is_special' => $additionalFields['is_special'] ?? false,
        ];

        if ($room_number !== null) {
            $orderData['room_number'] = $room_number;
        }

        if (!empty($additionalFields['reserve_number'])) {
            $orderData['reserve_number'] = $additionalFields['reserve_number'];
        }

        if (!empty($additionalFields['desc_number'])) {
            $orderData['desc_number'] = $additionalFields['desc_number'];
        }

        if (!empty($calculate['expired_discount_info'])) {
            $orderData['expired_discount_info'] = json_encode($calculate['expired_discount_info']);
        }

        $parentOrder = Order::query()->create($orderData);
        $parentOrder->product_price = $calculate['product_price'];

        foreach ($orderDetails as $index => $order) {
            $food = Food::findOrFail($order['food_id']);
            $item_price = intval(round($food->price * $order['quantity']));
            $item_discount = intval(round(($item_price / $total_price) * $calculate['discounted_price']));

            $childOrderData = [
                'parent_id' => $parentOrder->id,
                'food_id' => $order['food_id'],
                'price' => $item_price,
                'discounted_price' => $item_discount,
                'quantity' => $order['quantity'],
                'description' => $order['description'] ?? null,
                'discount_id' => $discount,
                'invoice_number' => $invoice_number,
                'service_type' => $additionalFields['service_type'] ?? null,
                'rate_service' => $child_service_fees[$index],
                'tax' => $child_taxes[$index],
            ];

            if ($room_number !== null) {
                $childOrderData['room_number'] = $room_number;
            }

            if (!empty($additionalFields['reserve_number'])) {
                $childOrderData['reserve_number'] = $additionalFields['reserve_number'];
            }

            Order::create($childOrderData);
        }

        if (!empty($calculate['club_points_used']) && $calculate['club_points_used'] > 0) {
            $this->deductClubPoints(
                $additionalFields['reserve_number'] ?? null,
                $additionalFields['customer_id'] ?? null,
                $calculate['club_points_used'],
                $total_price
            );
        }

        if (!empty($discount)){
            Discount::find($discount)->increment('usage_count');
        }

        return $parentOrder;
    }
    private function deductClubPoints($reserve_number, $customer_id, $pointsUsed, $total_price)
    {
        if ($reserve_number) {
            $guestUser = \App\Models\GuestUser::where('Reserve', $reserve_number)->first();
            if ($guestUser) {
                $points = $guestUser->total_price - $pointsUsed;
                ResidentCustomerPoint::create([
                    'reserve_number' => $reserve_number,
                    'points' => $points,
                    'price_purchased' => $total_price,
                ]);
            }
        } elseif ($customer_id) {
            $customer = \App\Models\Customer::find($customer_id);
            if ($customer) {
                $customer->decrement('points', $pointsUsed);
            }
        }
    }
    public function orderComplete(CompleteOrderRequest $request,Order $order)
    {
        try {
            $data = [
                'payment_method' => $request->payment_method,
                'serial_number' => $request->serial_number ?? null,
            ];
            if (!empty($request->reserve_number)) {
                $data['reserve_number'] = $request->reserve_number;
                $data['customer_id'] = null;
            } elseif (!empty($request->name) && !empty($request->phone)) {
                $customer = Customer::updateOrCreate(
                    ['phone' => $request->phone],
                    ['name' => $request->name]
                );
                $data['customer_id'] = $customer->id;
                $data['reserve_number'] = null;
            }
           $payment_method =  Type::query()->find($request->payment_method);
            $posPaymentMethods = [
                'payment-method-cash',
                'payment-method-iranzamin-pos',
                'payment-method-saderat-pos',
                'payment-method-mellat-pos',
                'payment-method-refah-pos',
                'payment-method-etebary-pos',
                'payment-method-meli-pos'
            ];

            if (in_array($payment_method?->slug, $posPaymentMethods) && !empty($order->reserve_number)) {
                if (empty($request->phone) || empty($request->name)) {
                    return (new Response())->ApiResponse([
                        'message' => 'شماره تلفن و نام کاربر الزامی میباشد.',
                        'status' => 400
                    ]);
                }

                $order->update([
                    'reserve_number' => null
                ]);

                $customer = Customer::updateOrCreate(
                    ['phone' => $request->phone],
                    ['name' => $request->name]
                );

                $data['customer_id'] = $customer->id;
            }

            $discount_code = collect([
                $request->discount_code,
                $request->discount_global_code,
                $request->discount_normal_code,
            ])->filter()->first();

            if ($request->boolean('use_next_purchase_discount')) {
                $nextPurchaseDiscount = Discount::query()->where('scope', 'next_purchase')
                    ->where('is_active', true)
                    ->where(function ($q) use ($request, $data) {
                        if ($request->customer_id || !empty($data['customer_id'])) {
                            $q->where('customer_id', $request->customer_id ?? $data['customer_id']);
                        }
                        if ($request->reserve_number || !empty($data['reserve_number'])) {
                            $q->orWhere('reserve_number', $request->reserve_number ?? $data['reserve_number']);
                        }
                    })
                    ->where('expires_at', '>', now())
                    ->whereColumn('usage_count', '<', 'usage_limit')
                    ->first();
                if ($nextPurchaseDiscount) {
                    $discount_code = $nextPurchaseDiscount->code;
                }
            }

            $data['discount_code'] = $discount_code ?? null;
            $data['discount_value'] = $request->discount_value ?? null;
            $data['discount_type'] = $request->discount_type ?? null;
            $data['use_club_points'] = $request->boolean('use_club_points');

            return $this->finalizeOrder($request, $order, $data);
        } catch (\Exception $exception) {
            return $this->handleCompleteOrderError($request, $exception);
        }
    }
    protected function finalizeOrder(Request $request, Order $order, array $additionalFields)
    {
        $shouldRecalculate = !empty($additionalFields['discount_code'])
            || (!empty($additionalFields['discount_type']) && !empty($additionalFields['discount_value']))
            || (!empty($additionalFields['use_club_points']) && $additionalFields['use_club_points'] === true);

        if ($shouldRecalculate) {
            $oldDiscountId = $order->discount_id;
            $orderDetails = [];
            foreach ($order->children as $item) {
                $orderDetails[] = [
                    'food_id' => $item->food_id,
                    'quantity' => $item->quantity,
                    'description' => $item->description,
                ];
            }

            $total_price = 0;
            foreach ($orderDetails as $od) {
                $food = Food::findOrFail($od['food_id']);
                $total_price += intval(round($food->price * $od['quantity']));
            }

            $applyRateService = $order->rate_service > 0;
            $orderRepo = app(OrderRepository::class);
            $calculate = $orderRepo->calculatePrice(
                $orderDetails,
                $total_price,
                $additionalFields['discount_code'] ?? null,
                $additionalFields['discount_value'] ?? null,
                $additionalFields['discount_type'] ?? null,
                $applyRateService,
                $additionalFields['reserve_number'] ?? $order->reserve_number,
                $additionalFields['customer_id'] ?? $order->customer_id,
                false,
                $additionalFields['use_club_points'] ?? false
            );

            $discount = null;
            if (!empty($calculate['discount'])) {
                $discount = $calculate['discount'];
            } elseif (empty($calculate['discount']) && !empty($additionalFields['discount_value']) && !empty($additionalFields['discount_type'])) {
                $discount = Discount::query()->create([
                    'code' => null,
                    'name' => 'تخفیف دستی',
                    'discount_value' => $additionalFields['discount_value'],
                    'discount_type' => $additionalFields['discount_type'],
                    'is_active' => true,
                    'scope' => 'in_order'
                ])->id;
            }

            $setting = Setting::first();
            $total_service_fee = 0;
            $total_tax = 0;
            $child_service_fees = [];
            $child_taxes = [];

            foreach ($orderDetails as $index => $od) {
                $food = Food::findOrFail($od['food_id']);
                $item_price = intval(round($food->price * $od['quantity']));
                $item_service_fee = 0;
                $applicableTotal = $total_price;
                $item_discount = ($calculate['discounted_price'] > 0 && $applicableTotal > 0)
                    ? intval(round(($item_price / $applicableTotal) * $calculate['discounted_price']))
                    : 0;
                $item_price_after_discount = $item_price - $item_discount;
                if ($applyRateService) {
                    $item_service_fee = intval(round($item_price_after_discount * $setting->rate_service / 100));
                }
                $item_tax = intval(round(($item_price_after_discount + $item_service_fee) * $setting->tax / 100));
                $child_service_fees[$index] = $item_service_fee;
                $child_taxes[$index] = $item_tax;
                $total_service_fee += $item_service_fee;
                $total_tax += $item_tax;
            }

            $order->update([
                'price' => $calculate['price'],
                'total_price' => $calculate['total_price'],
                'rate_service' => $total_service_fee,
                'tax' => $total_tax,
                'discounted_price' => (int) round($calculate['discounted_price']),
                'discount_id' => $discount,
                'club_points_used' => $calculate['club_points_used'] ?? 0,
                'expired_discount_info' => $calculate['expired_discount_info'] ?? null,
            ]);

            foreach ($order->children as $index => $item) {
                $food = Food::findOrFail($item->food_id);
                $item_price = intval(round($food->price * $item->quantity));
                $item_discount = ($total_price > 0 && $calculate['discounted_price'] > 0)
                    ? intval(round(($item_price / $total_price) * $calculate['discounted_price']))
                    : 0;
                $item->update([
                    'discount_id' => $discount,
                    'discounted_price' => $item_discount,
                    'rate_service' => $child_service_fees[$index] ?? 0,
                    'tax' => $child_taxes[$index] ?? 0,
                ]);
            }

            if ($oldDiscountId != $discount) {
                if (!empty($oldDiscountId)) {
                    $oldModel = Discount::find($oldDiscountId);
                    if ($oldModel && $oldModel->usage_count > 0) {
                        $oldModel->decrement('usage_count');
                    }
                }
                if (!empty($discount)) {
                    Discount::find($discount)?->increment('usage_count');
                }
            }
        }

        $persistFields = [
            'payment_method' => isset($additionalFields['payment_method']) ? (int) $additionalFields['payment_method'] : $order->payment_method,
            'serial_number' => $additionalFields['serial_number'] ?? $order->serial_number,
            'reserve_number' => $additionalFields['reserve_number'] ?? $order->reserve_number,
            'customer_id' => $additionalFields['customer_id'] ?? $order->customer_id,
        ];
        $order->update(array_merge($persistFields, [
            'status' => Type::query()->where('slug', TypeSlug::ORDER_STATUS_COMPLETE)->first()->id,
        ]));

        $itemsGroupedByPrinter = [];
        foreach ($order->children as $item) {
            $item->update([
                'reserve_number' => $order->reserve_number ?? 0,
            ]);

            $printer = $this->getPrinterForItem($item);
            if ($printer) {
                $itemsGroupedByPrinter[$printer->id][] = $item;
            }
        }

        $this->awardClubPoints($order);

        DB::statement('EXEC dbo.sp_insert_POS_Table ?', [$order->invoice_number]);

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
                if ($printer->type == Type::query()->where('slug', TypeSlug::LASER_PRINTER)->first()->id) {
                    $printService->printHPInvoice($order, $printer->name);
                } else {
                    return (new Response())->ApiResponse([
                        'status' => 400,
                        'message' => 'هیچ پرینتری برای این سفارش یافت نشد'
                    ]);
                }
            }
        }

        Log::query()->create([
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
            'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_UPDATE)->first()->id,
            'loggable_type' => Order::class,
            'loggable_id' => $order->id,
            'message' => 'تکمیل وضعیت سفارش با موفقیت انجام شد',
            'date' => now(),
            'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id,
        ]);

        $setting = Setting::first();
        if ($setting && $setting->send_order_complete_sms && $setting->order_complete_sms_template) {
            $mobile = collect([
                $order->customer?->phone,
                $order->reserve?->Mobile,
            ])->filter()->first();

            if ($mobile) {
                $smsData = [
                    'name' => $order->customer?->name ?? $order->reserve?->GuestName ?? 'مشتری گرامی',
                    'order_number' => $order->invoice_number,
                    'price' => $order->total_price,
                    'date' => Jalalian::now()->format('Y/m/d H:i'),
                ];
                SendOrderCompleteSms::dispatch($mobile, $setting->order_complete_sms_template, $smsData);
            }
        }
        
        // ایجاد تخفیف خرید بعدی بر اساس تنظیمات فعال
        $nextDiscountSettings = NextPurchaseDiscount::getLatestActive();

        $hasActiveNextPurchaseDiscount = false;
        if ($order->customer_id || $order->reserve_number) {
            $hasActiveNextPurchaseDiscount = Discount::where('scope', 'next_purchase')
                ->where('is_active', true)
                ->where(function ($q) use ($order) {
                    if ($order->customer_id) {
                        $q->where('customer_id', $order->customer_id);
                    }
                    if ($order->reserve_number) {
                        $q->orWhere('reserve_number', $order->reserve_number);
                    }
                })
                ->where('expires_at', '>', now())
                ->whereColumn('usage_count', '<', 'usage_limit')
                ->exists();
        }


        if (!$hasActiveNextPurchaseDiscount && $nextDiscountSettings && $nextDiscountSettings->canApplyForCurrentOrder($order->total_price)) {
            $shouldApply = true;

            // Check Profit Manager
            if (!empty($nextDiscountSettings->profit_manager_ids)) {
                $user = $order->user;
                if (!$user || !in_array($user->profit_manager_id, $nextDiscountSettings->profit_manager_ids)) {
                    $shouldApply = false;
                }
            }

            // Check Customer Type
            if ($shouldApply && !empty($nextDiscountSettings->target_customer_types)) {
                $type = $order->reserve_number ? 'resident' : 'Non_resident';
                if (!in_array($type, $nextDiscountSettings->target_customer_types)) {
                    Logger::info('type ' . $type, [$nextDiscountSettings->target_customer_types]);
                    $shouldApply = false;
                }
            }

            if ($shouldApply) {
                $discount = $nextDiscountSettings->createDiscount(
                    $order->total_price,
                    $order->customer_id,
                    $order->reserve_number
                );

                if ($discount) {
                    $mobile = collect([
                        $order->customer?->phone,
                        $order->reserve?->Mobile,
                    ])->filter()->first();

                    if ($mobile) {
                        $smsData = [
                            'name' => $order->customer?->name ?? $order->reserve?->Name ?? 'مشتری گرامی',
                            'order_number' => $order->invoice_number,
                        ];
 
                    Logger::error('send to job with delay ' . now()->addDays($nextDiscountSettings->days ?? 0));
                        // Send Discount SMS
                        $job = SendNextPurchaseDiscountToCustomers::dispatch(
                            $mobile,
                            $discount,
                            $nextDiscountSettings->discount_sms_template,
                            $smsData
                        );

                        if (!is_null($nextDiscountSettings->days)) {
                            $job->delay(now()->addDays((int) $nextDiscountSettings->days));
                        }


                        // Send Reminder SMS
                        if ($nextDiscountSettings->reminder_days_before_expiration) {
                            $reminderDelay = $discount->expires_at->copy()->subDays($nextDiscountSettings->reminder_days_before_expiration);
                            if ($reminderDelay->isFuture()) {
                                SendNextPurchaseDiscountToCustomers::dispatch(
                                    $mobile,
                                    $discount,
                                    $nextDiscountSettings->reminder_sms_template,
                                    $smsData
                                )->delay($reminderDelay);
                            }
                        }
                    }
                }
            }
        }

        return (new Response())->ApiResponse([
            'message' => 'عملیات موفقیت آمیز بود',
            'items' => Order::with([
                'customer',
                'user',
                'status',
                'reserve',
                'paymentMethod',
                'discount',
                'food',
                'parent',
                'children.food',
                'children.status',
            ])->find($order->id)
        ]);
    }

    private function awardClubPoints(Order $order)
    {
        $clubSetting = ClubSetting::getActive();

        if (!$clubSetting) {
            return;
        }

        $earnedPoints = $clubSetting->calculateEarnedPoints($order->total_price);

        if ($earnedPoints <= 0) {
            return;
        }

        if ($order->reserve_number) {
            ResidentCustomerPoint::create([
                'reserve_number' => $order->reserve_number,
                'points' => $earnedPoints,
                'price_purchased' => $order->total_price,
            ]);
        }
    }

    protected function handleCompleteOrderError(Request $request, \Exception $exception)
    {
        Log::query()->create([
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
            'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_UPDATE)->first()->id,
            'loggable_type' => Order::class,
            'loggable_id' => null,
            'message' => 'تکمیل وضعیت سفارش با خطا مواجه شد',
            'date' => now(),
            'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_FAILED)->first()->id,
        ]);

        return (new Response())->ApiResponse([
            'status' => 500,
            'message' => 'خطای سیستمی رخ داده است.',
            'error_message' => $exception->getMessage(),
            'line' => $exception->getLine(),
        ]);
    }

    private function getPrinterForItem($item)
    {
        $type = Type::query()->where('slug', TypeSlug::LASER_PRINTER)->first();
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

    public function preInvoice(Request $request, Order $order)
    {
        $orderDetails = [];
        foreach ($order->children as $item) {
            $orderDetails[] = [
                'food_id' => $item->food_id,
                'quantity' => $item->quantity,
                'description' => $item->description,
            ];
        }
        $total_price = 0;
        foreach ($orderDetails as $od) {
            $food = Food::findOrFail($od['food_id']);
            $total_price += intval(round($food->price * $od['quantity']));
        }

        $request->validate([
            'name' => 'nullable|string',
            'phone' => 'nullable|string',
            'reserve_number' => 'nullable|string|exists:inhouseList,Reserve',
            'discount_code' => ['nullable', 'string'],
            'discount_normal_code' => ['nullable', 'string', new CheckDiscountValid('normal')],
            'discount_global_code' => ['nullable', 'string', new CheckDiscountValid('global')],
            'use_next_purchase_discount' => ['nullable', 'boolean'],
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => [
                'nullable', 'numeric', 'min:0',
                function ($attribute, $value, $fail) use ($total_price, $request) {
                    if (!$request->discount_type) {
                        return;
                    }
                    if ($request->discount_type === 'percentage') {
                        if ($value > 100) {
                            $fail('درصد تخفیف نمی‌تواند بیشتر از 100 باشد');
                            return;
                        }
                        $discountAmount = ($value / 100) * $total_price;
                        if ($discountAmount > $total_price) {
                            $fail('مقدار تخفیف محاسبه‌شده نمی‌تواند بیشتر از جمع کل سفارش باشد');
                        }
                    }
                    if ($request->discount_type === 'fixed' && $value > $total_price) {
                        $fail('مقدار تخفیف نمی‌تواند بیشتر از جمع کل سفارش باشد');
                    }
                },
            ],
            'use_club_points' => [
                'nullable','boolean',
                function ($attribute, $value, $fail) use ($request, $order) {
                    if ($value == true) {
                        $hasReserveNumber = !empty($request->reserve_number) || !empty($order->reserve_number);
                        $hasCustomerId = !empty($request->customer_id) || !empty($order->customer_id);
                        if (!$hasReserveNumber && !$hasCustomerId) {
                            $fail('برای استفاده از امتیاز باشگاه مشتریان، باید شماره رزرو یا مشتری را انتخاب کنید');
                        }
                    }
                }
            ],
        ]);
        $status = Type::query()->where('slug', TypeSlug::ORDER_STATUS_COMPLETE)->first();
        if ($order->status == $status->id){
            return (new Response())->ApiResponse([
                'message' => 'وضعیت فاکتور تکمیل شده میباشد نمیتوانید ان را تغییر دهید'
            ]);
        }
        try {

            $data = [];
            if (!empty($request->reserve_number)) {
                $data['reserve_number'] = $request->reserve_number;
                $data['customer_id'] = null;
            } elseif (!empty($request->name) && !empty($request->phone)) {
                $customer = Customer::updateOrCreate(
                    ['phone' => $request->phone],
                    ['name' => $request->name]
                );
                $data['customer_id'] = $customer->id;
                $data['reserve_number'] = null;
            }
            $order->update($data);
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Order::class,
                'loggable_id' => $order->id,
                'message' => 'ثبت سفارش با موفقیت انجام شد',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);

            $discountFields = [
                'discount_code' => $request->discount_code,
                'discount_normal_code' => $request->discount_normal_code,
                'discount_global_code' => $request->discount_global_code,
                'use_next_purchase_discount' => $request->use_next_purchase_discount,
                'discount_value' => $request->discount_value,
                'use_club_points' => $request->use_club_points
            ];
            $activeDiscounts = collect($discountFields)->filter(fn($value) => !empty($value))->count();
            if ($activeDiscounts > 1) {
                return (new Response())->ApiResponse([
                    'status' => 422,
                    'message' => 'فقط یک نوع تخفیف قابل استفاده است',
                ]);
            }

            $discount_code = collect([
                $request->discount_code,
                $request->discount_global_code,
                $request->discount_normal_code,
            ])->filter()->first();

            if ($request->boolean('use_next_purchase_discount')) {
                $customerId = $request->customer_id ?? $order->customer_id;
                $reserveNumber = $request->reserve_number ?? $order->reserve_number;
                $nextPurchaseDiscount = Discount::where('scope', 'next_purchase')
                    ->where('is_active', true)
                    ->where(function ($q) use ($customerId, $reserveNumber) {
                        if ($customerId) {
                            $q->where('customer_id', $customerId);
                        }
                        if ($reserveNumber) {
                            $q->orWhere('reserve_number', $reserveNumber);
                        }
                    })
                    ->where('expires_at', '>', now())
                    ->whereColumn('usage_count', '<', 'usage_limit')
                    ->first();
                if ($nextPurchaseDiscount) {
                    $discount_code = $nextPurchaseDiscount->code;
                }
            }

            $manual_discount_value = $request->discount_value;
            $manual_discount_type = $request->discount_type;
            $use_club_points = $request->boolean('use_club_points');

            // اگر درخواست هیچ فیلد تخفیفی نداشت، تخفیف فعلی سفارش حفظ شود (سازگاری با نسخه‌های قدیمی فرانت)
            $hasDiscountInput = $request->has('selected_discount_type')
                || $request->filled('discount_code')
                || $request->filled('discount_normal_code')
                || $request->filled('discount_global_code')
                || $request->filled('discount_value')
                || $request->has('use_next_purchase_discount')
                || $request->has('use_club_points');

            if (!$hasDiscountInput && empty($discount_code)) {
                if (($order->club_points_used ?? 0) > 0) {
                    $use_club_points = true;
                } elseif ($order->discount) {
                    if (!empty($order->discount->code)) {
                        $discount_code = $order->discount->code;
                    } elseif ($order->discount->scope === 'in_order') {
                        $manual_discount_value = $order->discount->discount_value;
                        $manual_discount_type = $order->discount->discount_type;
                    }
                }
            }

            $applyRateService = $order->rate_service > 0;
            $orderRepo = app(OrderRepository::class);
            $calculate = $orderRepo->calculatePrice(
                $orderDetails,
                $total_price,
                $discount_code,
                $manual_discount_value ?? null,
                $manual_discount_type ?? null,
                $applyRateService,
                $request->reserve_number ?? $order->reserve_number,
                $request->customer_id ?? $order->customer_id,
                false,
                $use_club_points
            );

            $oldDiscountId = $order->discount_id;
            $newDiscountId = null;
            if (!empty($calculate['discount'])) {
                $newDiscountId = $calculate['discount'];
            } elseif (empty($calculate['discount']) && !empty($manual_discount_value) && !empty($manual_discount_type)) {
                if ($order->discount && $order->discount->scope === 'in_order'
                    && $order->discount->discount_value == $manual_discount_value
                    && $order->discount->discount_type == $manual_discount_type) {
                    $newDiscountId = $order->discount_id;
                } else {
                    $newDiscountId = Discount::query()->create([
                        'code' => null,
                        'name' => 'تخفیف دستی',
                        'discount_value' => $manual_discount_value,
                        'discount_type' => $manual_discount_type,
                        'is_active' => true,
                        'scope' => 'in_order'
                    ])->id;
                }
            }

            $setting = Setting::first();
            $total_service_fee = 0;
            $total_tax = 0;
            $child_service_fees = [];
            $child_taxes = [];
            foreach ($orderDetails as $index => $od) {
                $food = Food::findOrFail($od['food_id']);
                $item_price = intval(round($food->price * $od['quantity']));
                $item_service_fee = 0;
                $item_discount = ($total_price > 0 && $calculate['discounted_price'] > 0)
                    ? intval(round(($item_price / $total_price) * $calculate['discounted_price']))
                    : 0;
                $item_price_after_discount = $item_price - $item_discount;
                if ($applyRateService) {
                    $item_service_fee = intval(round($item_price_after_discount * $setting->rate_service / 100));
                }
                $item_tax = intval(round(($item_price_after_discount + $item_service_fee) * $setting->tax / 100));
                $child_service_fees[$index] = $item_service_fee;
                $child_taxes[$index] = $item_tax;
                $total_service_fee += $item_service_fee;
                $total_tax += $item_tax;
            }

            $order->update([
                'price' => $calculate['price'],
                'total_price' => $calculate['total_price'],
                'rate_service' => $total_service_fee,
                'tax' => $total_tax,
                'discounted_price' => (int) round($calculate['discounted_price']),
                'discount_id' => $newDiscountId,
                'club_points_used' => $calculate['club_points_used'] ?? 0,
                'expired_discount_info' => $calculate['expired_discount_info'] ?? null,
            ]);

            foreach ($order->children as $index => $item) {
                $food = Food::findOrFail($item->food_id);
                $item_price = intval(round($food->price * $item->quantity));
                $item_discount = ($total_price > 0 && $calculate['discounted_price'] > 0)
                    ? intval(round(($item_price / $total_price) * $calculate['discounted_price']))
                    : 0;
                $item->update([
                    'discount_id' => $newDiscountId,
                    'discounted_price' => $item_discount,
                    'rate_service' => $child_service_fees[$index] ?? 0,
                    'tax' => $child_taxes[$index] ?? 0,
                ]);
            }

            if ($oldDiscountId != $newDiscountId) {
                if (!empty($oldDiscountId)) {
                    $oldModel = Discount::find($oldDiscountId);
                    if ($oldModel && $oldModel->usage_count > 0) {
                        $oldModel->decrement('usage_count');
                    }
                }
                if (!empty($newDiscountId)) {
                    Discount::find($newDiscountId)?->increment('usage_count');
                }
            }

            return (new Response())->ApiResponse([
                'message' => 'عملیات موفقیت آمیز بود',
                // TODO: remove debug block after verifying discount fix on production
                'debug_discount_fix' => [
                    'code_version' => 'discount-fix-v4',
                    'request_had_discount_fields' => $hasDiscountInput,
                    'effective_discount_code' => $discount_code,
                    'effective_discount_value' => $manual_discount_value,
                    'effective_discount_type' => $manual_discount_type,
                    'calculated_discounted_price' => $calculate['discounted_price'],
                ],
                'items' => Order::with([
                    'customer',
                    'user',
                    'status',
                    'paymentMethod',
                    'discount',
                    'food',
                    'parent',
                    'children.food',
                    'children.status',
                    'reserve'
                ])->find($order->id)
            ]);
        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Order::class,
                'loggable_id' => null,
                'message' => 'ثبت سفارش با خطا مواجه شد',
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
    public function update(Request $request, Order $order)
    {
        try {
            $pendingStatus = Type::query()->where('slug', TypeSlug::ORDER_STATUS_PENDING)->first()->id;
            if ($order->status != $pendingStatus) {
                return (new Response())->ApiResponse([
                    'status' => 400,
                    'message' => 'تنها سفارشات ثبت موقت قابل ویرایش هستند.',
                ]);
            }

            $total_price = 0;
            foreach ($request->order as $order_item) {
                $food = Food::findOrFail($order_item['food_id']);
                $total_price += intval(round($food->price * $order_item['quantity']));
            }

            $discountFields = [
                'discount_normal_code' => $request->discount_normal_code,
                'discount_global_code' => $request->discount_global_code,
                'use_next_purchase_discount' => $request->use_next_purchase_discount,
                'discount_value' => $request->discount_value,
                'use_club_points' => $request->use_club_points
            ];

            $activeDiscounts = collect($discountFields)->filter(fn($value) => !empty($value))->count();

            if ($activeDiscounts > 1) {
                return (new Response())->ApiResponse([
                    'status' => 422,
                    'message' => 'فقط یک نوع تخفیف قابل استفاده است',
                ]);
            }

            $discount_code = collect([
                $request->discount_global_code,
                $request->discount_normal_code,
            ])->filter()->first();

            if ($request->boolean('use_next_purchase_discount')) {
                $nextPurchaseDiscount = Discount::where('scope', 'next_purchase')
                    ->where('is_active', true)
                    ->where(function ($q) use ($request) {
                        if ($request->customer_id) {
                            $q->where('customer_id', $request->customer_id);
                        }
                        if ($request->reserve_number) {
                            $q->orWhere('reserve_number', $request->reserve_number);
                        }
                    })
                    ->where('expires_at', '>', now())
                    ->whereColumn('usage_count', '<', 'usage_limit')
                    ->first();

                if ($nextPurchaseDiscount) {
                    $discount_code = $nextPurchaseDiscount->code;
                }
            }

            $validationRules = [
                'order' => 'required|array',
                'order.*.food_id' => [
                    'required',
                    'integer',
                    Rule::exists('food', 'id')->whereNull('deleted_at'),
                ],
                'service_type' => 'required|in:takeaway,dine_in,room_service',
                'desc_number' => [
                    'nullable',
                    'string',
                    'required_if:service_type,dine_in',
                ],
                'reserve_number' => [
                    'nullable',
                    'string',
                    'exists:InhouseList,Reserve'
                ],
                'customer_id' => [
                    'nullable',
                    'string',
                    'exists:customers,id'
                ],
                'discount_normal_code' => [
                    'nullable',
                    'string',
                    new CheckDiscountValid('normal'),
                ],
                'discount_global_code' => [
                    'nullable',
                    'string',
                    new CheckDiscountValid('global'),
                ],
                'use_next_purchase_discount' => [
                    'nullable',
                    'boolean',
                    function ($attribute, $value, $fail) use ($total_price, $request) {
                        if ($value != true) {
                            return;
                        }

                        $query = Discount::where('scope', 'next_purchase')
                            ->where('is_active', true)
                            ->where(function ($q) use ($request) {
                                if ($request->customer_id) {
                                    $q->where('customer_id', $request->customer_id);
                                }
                                if ($request->reserve_number) {
                                    $q->orWhere('reserve_number', $request->reserve_number);
                                }
                            })
                            ->whereColumn('usage_count', '<', 'usage_limit');
                        
                        $discount = $query->first();

                        if (!$discount) {
                            $fail('تخفیف خرید بعدی فعالی برای شما یافت نشد');
                            return;
                        }

                        if ($discount->isExpired()) {
                            $fail('تخفیف خرید بعدی شما منقضی شده است|expired');
                            return;
                        }

                        if ($discount->minimum_price && $total_price < $discount->minimum_price) {
                            $fail('مبلغ سفارش کمتر از حداقل مبلغ مجاز برای استفاده از تخفیف خرید بعدی است');
                        }
                    }
                ],
                'discount_type' => 'nullable|in:percentage,fixed',
                'discount_value' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    function ($attribute, $value, $fail) use ($request, $total_price) {
                        if (!$request->discount_type) {
                            return;
                        }

                        if ($request->discount_type === 'percentage') {
                            if ($value > 100) {
                                $fail('درصد تخفیف نمی‌تواند بیشتر از 100 باشد');
                                return;
                            }

                            $discountAmount = ($value / 100) * $total_price;
                            if ($discountAmount > $total_price) {
                                $fail('مقدار تخفیف محاسبه‌شده نمی‌تواند بیشتر از جمع کل سفارش باشد');
                            }
                        }

                        if ($request->discount_type === 'fixed' && $value > $total_price) {
                            $fail('مقدار تخفیف نمی‌تواند بیشتر از جمع کل سفارش باشد');
                        }
                    },
                ],
                'use_club_points' => [
                    'nullable',
                    'boolean',
                    function ($attribute, $value, $fail) use ($request) {
                        if ($value == true) {
                            $hasReserveNumber = !empty($request->reserve_number);
                            $hasCustomerId = !empty($request->customer_id);

                            if (!$hasReserveNumber && !$hasCustomerId) {
                                $fail('برای استفاده از امتیاز باشگاه مشتریان، باید شماره رزرو یا مشتری را انتخاب کنید');
                            }
                        }
                    }
                ],
                'order.*.quantity' => 'required|integer|min:1',
                'order.*.description' => 'nullable|string',
                'rate_service' => 'nullable|in:0,1',
            ];

            if ($request->service_type !== 'takeaway' && $request->service_type !== 'room_service') {
                $validationRules['desc_number'][] = new DescNumberRule($order->id);
            }

            $validationResult = (new validateRequest())->validate($request->all(), $validationRules);
            if ($validationResult !== true) {
                return $validationResult;
            }

            $validated = $request->all();

            $changedItems = $this->getChangedOrderItems($order, $validated['order']);

            if (
                empty($changedItems['added']) &&
                empty($changedItems['removed']) &&
                empty($changedItems['updated']) &&
                !$this->hasOrderChanged($order, $validated)
            ) {
                return (new Response())->ApiResponse([
                    'status' => 200,
                    'message' => 'هیچ تغییری در سفارش اعمال نشده است.',
                    'items' => $order->load([
                        'customer', 'user', 'status', 'paymentMethod', 'discount', 'food', 'parent', 'children.food', 'children.status',
                    ]),
                ]);
            }

            DB::beginTransaction();

            $oldDiscountId = $order->discount_id;
            $oldClubPointsUsed = $order->club_points_used ?? 0;

            $orderRepo = app(OrderRepository::class);
            $calculate = $orderRepo->calculatePrice(
                $validated['order'],
                $total_price,
                $discount_code,
                $request->discount_value ?? null,
                $request->discount_type ?? null,
                $request->rate_service == 1,
                $request->reserve_number ?? null,
                $request->customer_id ?? null,
                $request->boolean('is_special'),
                $request->boolean('use_club_points')
            );

            $newDiscountId = null;

            if (empty($calculate['discount']) && !empty($request->discount_value) && !empty($request->discount_type)) {
                $newDiscountId = Discount::query()->create([
                    'code' => null,
                    'name' => 'تخفیف دستی',
                    'discount_value' => $request->discount_value,
                    'discount_type' => $request->discount_type,
                    'is_active' => true,
                    'scope' => 'in_order'
                ])->id;
            } elseif (!empty($calculate['discount'])) {
                $newDiscountId = $calculate['discount'];
            }

            if ($oldDiscountId != $newDiscountId) {
                if ($oldDiscountId) {
                    $oldDiscountModel = Discount::find($oldDiscountId);
                    if ($oldDiscountModel && $oldDiscountModel->usage_count > 0) {
                        $oldDiscountModel->decrement('usage_count');
                    }
                }

                if ($newDiscountId) {
                    $newDiscountModel = Discount::find($newDiscountId);
                    if ($newDiscountModel) {
                        $newDiscountModel->increment('usage_count');
                    }
                }
            }

            if ($oldClubPointsUsed > 0) {
                $this->refundClubPoints(
                    $order->reserve_number,
                    $order->customer_id,
                    $oldClubPointsUsed
                );
            }

            $order->children()->delete();

            $setting = Setting::first();
            $total_service_fee = 0;
            $total_tax = 0;
            $child_service_fees = [];
            $child_taxes = [];

            foreach ($validated['order'] as $item) {
                $food = Food::findOrFail($item['food_id']);
                $item_price = intval(round($food->price * $item['quantity']));
                $item_service_fee = 0;
                $item_price_after_discount = $item_price - intval(round(($item_price / $total_price) * $calculate['discounted_price']));
                if ($request->rate_service == 1) {
                    $item_service_fee = intval(round($item_price_after_discount * $setting->rate_service / 100));
                }
                $item_tax = intval(round(($item_price_after_discount + $item_service_fee) * $setting->tax / 100));
                $child_service_fees[] = $item_service_fee;
                $child_taxes[] = $item_tax;
                $total_service_fee += $item_service_fee;
                $total_tax += $item_tax;
            }

            $room_number = null;
            if (!empty($validated['reserve_number'])) {
                $room_number = GuestUser::query()->where('Reserve', $validated['reserve_number'])->first()->Room;
            }

            foreach ($validated['order'] as $index => $item) {
                $food = Food::findOrFail($item['food_id']);
                $item_price = intval(round($food->price * $item['quantity']));
                $item_discount = intval(round(($item_price / $total_price) * $calculate['discounted_price']));

                $order->children()->create([
                    'food_id' => $item['food_id'],
                    'quantity' => $item['quantity'],
                    'description' => $item['description'] ?? null,
                    'price' => $item_price,
                    'discounted_price' => $item_discount,
                    'discount_id' => $newDiscountId,
                    'invoice_number' => $order->invoice_number,
                    'service_type' => $validated['service_type'],
                    'room_number' => $room_number,
                    'reserve_number' => $validated['reserve_number'] ?? null,
                    'rate_service' => $child_service_fees[$index],
                    'tax' => $child_taxes[$index],
                ]);
            }

            $order->update([
                'price' => $calculate['price'],
                'total_price' => $calculate['total_price'],
                'quantity' => array_sum(array_column($validated['order'], 'quantity')),
                'tax' => $total_tax,
                'discounted_price' => $calculate['discounted_price'],
                'discount_code' => $discount_code,
                'discount_value' => $request->discount_value,
                'discount_type' => $request->discount_type,
                'discount_id' => $newDiscountId,
                'service_type' => $validated['service_type'],
                'rate_service' => $total_service_fee,
                'desc_number' => $validated['desc_number'] ?? null,
                'reserve_number' => $validated['reserve_number'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'room_number' => $room_number,
                'club_points_used' => $calculate['club_points_used'] ?? 0,
                'expired_discount_info' => $calculate['expired_discount_info'] ?? null,
            ]);

            if (!empty($calculate['club_points_used']) && $calculate['club_points_used'] > 0) {
                $this->deductClubPoints(
                    $request->reserve_number ?? null,
                    $request->customer_id ?? null,
                    $calculate['club_points_used'],
                    $total_price
                );
            }

            Log::create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_UPDATE)->first()->id,
                'loggable_type' => Order::class,
                'loggable_id' => $order->id,
                'message' => 'ویرایش سفارش با موفقیت انجام شد',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id,
            ]);

            DB::commit();

            (new PrintService())->printChangedItems($order, $changedItems);

            $order->load(['customer', 'user', 'status', 'paymentMethod', 'discount', 'food', 'parent', 'children.food', 'children.status']);

            $response = [
                'message' => 'ویرایش سفارش با موفقیت انجام شد.',
                'items' => $order,
            ];

            return (new Response())->ApiResponse($response);

        } catch (\Exception $exception) {
            DB::rollBack();

            Log::create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_UPDATE)->first()->id,
                'loggable_type' => Order::class,
                'loggable_id' => $order->id,
                'message' => 'ویرایش سفارش با خطا مواجه شد: ' . $exception->getMessage(),
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_FAILED)->first()->id,
            ]);

            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ]);
        }
    }
    private function refundClubPoints($reserve_number, $customer_id, $pointsUsed)
    {
        if ($pointsUsed <= 0) {
            return;
        }

        if ($reserve_number) {
            ResidentCustomerPoint::create([
                'reserve_number' => $reserve_number,
                'points' => $pointsUsed,
                'price_purchased' => 0,
            ]);
        }
    }
    /**
     * فقط آیتم‌هایی که اضافه، حذف یا ویرایش شده‌اند را برمی‌گرداند
     */
    protected function getChangedOrderItems(Order $order, array $newOrderItems)
    {
        // آیتم‌های قبلی (فرزندان سفارش والد)
        $oldItems = $order->children->map(function ($item) {
            return [
                'food_id' => $item->food_id,
                'quantity' => $item->quantity,
                'description' => $item->description,
                'id' => $item->id,
                'food_name' => $item->food->name ?? '',
                'food' => $item->food, // برای نام غذا و ... (اختیاری)
            ];
        })->toArray();

        // آیتم‌های جدید
        $newItems = collect($newOrderItems)->map(function ($item) {
            return [
                'food_id' => $item['food_id'],
                'quantity' => $item['quantity'],
                'description' => $item['description'] ?? null,
                'food_name' => Food::find($item['food_id'])->name ?? '',
                'food' => Food::find($item['food_id']) ?? null,
            ];
        })->toArray();

        $changed = [
            'added' => [],
            'removed' => [],
            'updated' => [],
        ];

        // حذف‌شده‌ها (در old هست ولی در new نیست)
        foreach ($oldItems as $old) {
            $found = collect($newItems)->firstWhere('food_id', $old['food_id']);
            if (!$found) {
                $changed['removed'][] = $old;
            }
        }

        // اضافه‌شده‌ها (در new هست ولی در old نیست)
        foreach ($newItems as $new) {
            $found = collect($oldItems)->firstWhere('food_id', $new['food_id']);
            if (!$found) {
                $changed['added'][] = $new;
            }
        }

        // ویرایشی‌ها (فقط اگر تعداد یا توضیح تغییر کرده باشد)
        foreach ($newItems as $new) {
            $old = collect($oldItems)->firstWhere('food_id', $new['food_id']);
            if ($old) {
                if ($old['quantity'] != $new['quantity'] || $old['description'] != $new['description']) {
                    $changed['updated'][] = [
                        'old' => $old,
                        'new' => $new,
                    ];
                }
            }
        }

        return $changed;
    }
    /**
     * تغییرات غیر آیتمی (مثل سرویس یا نوع سفارش) را چک می‌کند
     */
    protected function hasOrderChanged($order, array $validated): bool
    {
        if ($order->service_type != ($validated['service_type'] ?? $order->service_type)) {
            return true;
        }

        if ($order->desc_number != ($validated['desc_number'] ?? $order->desc_number)) {
            return true;
        }

        if ($order->reserve_number != ($validated['reserve_number'] ?? $order->reserve_number)) {
            return true;
        }

        if ($order->customer_id != ($validated['customer_id'] ?? $order->customer_id)) {
            return true;
        }

        if ($order->rate_service != ($validated['rate_service'] ?? $order->rate_service)) {
            return true;
        }

        $newDiscountCode = collect([
            $validated['discount_global_code'] ?? null,
            $validated['discount_normal_code'] ?? null,
            $validated['discount_next_purchase_code'] ?? null,
        ])->filter()->first();

        if ($order->discount_code != $newDiscountCode) {
            return true;
        }

        if ($order->discount_next_purchase_code != ($validated['discount_next_purchase_code'] ?? null)) {
            return true;
        }

        if ($order->discount_value != ($validated['discount_value'] ?? null)) {
            return true;
        }

        if ($order->discount_type != ($validated['discount_type'] ?? null)) {
            return true;
        }

        $oldClubPointsUsed = $order->club_points_used ?? 0;
        $newUseClubPoints = $validated['use_club_points'] ?? false;

        if ($oldClubPointsUsed > 0 && !$newUseClubPoints) {
            return true;
        }

        if ($oldClubPointsUsed == 0 && $newUseClubPoints) {
            return true;
        }

        return false;
    }
    public function destroy(Request $request, Order $order)
    {
        try {
            // اطمینان از وضعیت در حال بررسی
            if ($order->status != Type::query()->where('slug', TypeSlug::ORDER_STATUS_PENDING)->first()->id) {
                return (new Response())->ApiResponse([
                    'status' => 400,
                    'message' => 'تنها سفارشات در حال بررسی قابل حذف هستند.',
                ]);
            }

            (new PrintService())->sendOrderToPrinters($order, 'destroy');

            // حذف سفارش و آیتم‌های مرتبط
            $order->children()->delete();
            $order->delete();

            // ثبت لاگ موفقیت‌آمیز
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_DESTROY)->first()->id,
                'loggable_type' => Order::class,
                'loggable_id' => $order->id,
                'message' => 'حذف سفارش با موفقیت انجام شد',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id,
            ]);

            return (new Response())->ApiResponse([
                'message' => 'حذف سفارش با موفقیت انجام شد.',
            ]);
        } catch (\Exception $exception) {
            // ثبت لاگ خطا
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_DESTROY)->first()->id,
                'loggable_type' => Order::class,
                'loggable_id' => $order->id,
                'message' => 'حذف سفارش با خطا مواجه شد',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_FAILED)->first()->id,
            ]);

            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ]);
        }
    }

    public function calculate(Request $request)
    {
        $total_price = 0;
        foreach ($request->order as $order) {
            $food = Food::findOrFail($order['food_id']);
            $total_price += intval(round($food->price * $order['quantity']));
        }

        $discountFields = [
            'discount_normal_code' => $request->discount_normal_code,
            'discount_global_code' => $request->discount_global_code,
            'use_next_purchase_discount' => $request->use_next_purchase_discount,
            'discount_value' => $request->discount_value,
            'use_club_points' => $request->use_club_points
        ];

        $activeDiscounts = collect($discountFields)->filter(fn($value) => !empty($value))->count();

        if ($activeDiscounts > 1) {
            return response()->json([
                'status' => 422,
                'message' => 'فقط یک نوع تخفیف قابل استفاده است',
            ], 422);
        }

        $validationResult = (new validateRequest())->validate($request->all(), [
            'discount_normal_code' => [
                'nullable',
                'string',
                new CheckDiscountValid('normal')
            ],
            'discount_global_code' => [
                'nullable',
                'string',
                new CheckDiscountValid('global')
            ],
            'use_next_purchase_discount' => [
                'nullable',
                'boolean'
            ],
            'discount_type' => 'nullable|in:percentage,fixed',
            'reserve_number' => [
                'nullable',
                'string',
                'exists:InhouseList,Reserve'
            ],
            'customer_id' => [
                'nullable',
                'string',
                'exists:customers,id'
            ],
            'use_club_points' => [
                'nullable',
                'boolean',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value == true) {
                        $hasReserveNumber = !empty($request->reserve_number);
                        $hasCustomerId = !empty($request->customer_id);

                        if (!$hasReserveNumber && !$hasCustomerId) {
                            $fail('برای استفاده از امتیاز باشگاه مشتریان، باید شماره رزرو یا مشتری را انتخاب کنید');
                        }
                    }
                }
            ],
            'discount_value' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request, $total_price) {
                    if (!$request->discount_type) {
                        return;
                    }

                    if ($request->discount_type === 'percentage') {
                        if ($value > 100) {
                            $fail('درصد تخفیف نمی‌تواند بیشتر از 100 باشد');
                            return;
                        }

                        $discountAmount = ($value / 100) * $total_price;
                        if ($discountAmount > $total_price) {
                            $fail('مقدار تخفیف محاسبه‌شده نمی‌تواند بیشتر از جمع کل سفارش باشد');
                        }
                    }

                    if ($request->discount_type === 'fixed' && $value > $total_price) {
                        $fail('مقدار تخفیف نمی‌تواند بیشتر از جمع کل سفارش باشد');
                    }
                },
            ],
            'is_special' => 'nullable|boolean',
            'rate_service' => 'required|in:0,1',
            'order' => 'required|array',
            'order.*.quantity' => 'required|integer|min:1',
            'order.*.food_id' => [
                'required',
                'integer',
                Rule::exists('food', 'id')->whereNull('deleted_at'),
            ],
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $discount_code = collect([
                $request->discount_global_code,
                $request->discount_normal_code,
            ])->filter()->first();

            if ($request->boolean('use_next_purchase_discount')) {
                $nextPurchaseDiscount = Discount::where('scope', 'next_purchase')
                    ->where('is_active', true)
                    ->where(function ($q) use ($request) {
                        if ($request->customer_id) {
                            $q->where('customer_id', $request->customer_id);
                        }
                        if ($request->reserve_number) {
                            $q->orWhere('reserve_number', $request->reserve_number);
                        }
                    })
                    ->where('expires_at', '>', now())
                    ->whereColumn('usage_count', '<', 'usage_limit')
                    ->first();

                if ($nextPurchaseDiscount) {
                    $discount_code = $nextPurchaseDiscount->code;
                }
            }

            $orderRepo = app(OrderRepository::class);
            $calculate = $orderRepo->calculatePrice(
                $request->order,
                $total_price,
                $discount_code,
                $request->discount_value,
                $request->discount_type,
                $request->rate_service == 1,
                $request->reserve_number,
                $request->customer_id,
                $request->boolean('is_special'),
                $request->boolean('use_club_points')
            );

            return (new Response())->ApiResponse([
                'items' => [
                    'discounted_price' => $calculate['discounted_price'],
                    'rate_service' => $calculate['service_fee'],
                    'tax_amount' => $calculate['tax_amount'],
                    'total_price' => $calculate['price'],
                    'product_price' => $calculate['product_price'],
                    'final_price' => $calculate['total_price'],
                    'club_points_used' => $calculate['club_points_used'],
                    'club_points_remaining' => $calculate['club_points_remaining'],
                    'expired_discount_info' => $calculate['expired_discount_info'] ?? null,
                ]
            ]);

        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Discount::class,
                'loggable_id' => null,
                'message' => 'نمایش نتیجه تخفیف با خطا مواجه شد',
                'date' => now(),
                'status' => Type::where('slug', TypeSlug::LOG_STATUS_FAILED)->first()->id,
            ]);

            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
