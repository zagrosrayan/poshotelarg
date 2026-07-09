<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\GuestUser;
use App\Service\Response;
use App\Service\validateRequest;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class ReportController extends Controller
{
    public function customers(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name' => 'nullable|string',
            'phone' => 'nullable|string',
            'month_since_last_purchase' => 'nullable|integer|min:0|max:120',
            'min_points' => 'nullable|integer|min:0',
            'max_points' => 'nullable|integer|min:0|gte:min_points',
            'created_at_from' => 'nullable|date',
            'created_at_to' => 'nullable|date|after_or_equal:created_at_from',
            'no_order_from' => 'nullable|date',
            'no_order_to' => 'nullable|date|after_or_equal:no_order_from',
            'food_id' => 'nullable|integer|exists:food,id',
            'excel' => 'nullable|boolean',
            'pdf' => 'nullable|boolean',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $orderBy = $request->order_by ?? 'created_at';
            $orderDirection = $request->order_direction ?? 'desc';

            $query = Customer::query()
                ->when($request->name, function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->name . '%');
                })
                ->when($request->phone, function ($q) use ($request) {
                    $q->where('phone', 'like', '%' . $request->phone . '%');
                })
                ->when($request->min_points !== null, function ($q) use ($request) {
                    $q->where('points', '>=', $request->min_points);
                })
                ->when($request->max_points !== null, function ($q) use ($request) {
                    $q->where('points', '<=', $request->max_points);
                })
                ->when($request->created_at_from, function ($q) use ($request) {
                    $q->whereDate('created_at', '>=', $request->created_at_from);
                })
                ->when($request->created_at_to, function ($q) use ($request) {
                    $q->whereDate('created_at', '<=', $request->created_at_to);
                })
                ->when($request->no_order_from && $request->no_order_to, function ($q) use ($request) {
                    $q->whereDoesntHave('orders', function ($query) use ($request) {
                        $query->whereDate('created_at', '>=', $request->no_order_from)
                            ->whereDate('created_at', '<=', $request->no_order_to);
                    });
                })
                ->when($request->month_since_last_purchase !== null, function ($q) use ($request) {
                    $monthsAgo = now()->subMonths($request->month_since_last_purchase);
                    $q->whereHas('orders', function ($query) use ($monthsAgo) {
                        $query->where('created_at', '<=', $monthsAgo)
                            ->whereNotExists(function ($subQuery) use ($monthsAgo) {
                                $subQuery->selectRaw(1)
                                    ->from('orders as o2')
                                    ->whereColumn('o2.customer_id', 'orders.customer_id')
                                    ->where('o2.created_at', '>', $monthsAgo);
                            });
                    });
                })
                ->when($request->food_id, function ($q) use ($request) {
                    $q->whereHas('orders.children', function ($query) use ($request) {
                        $query->where('food_id', $request->food_id);
                    });
                })->orderBy($orderBy, $orderDirection);

            if ($request->boolean('excel')) {
                ini_set('max_execution_time', 600);
                ini_set('memory_limit', '1024M');

                $customers = $query->get()->map(function ($customer) {
                    return [
                        'شناسه' => $customer->id,
                        'نام' => $customer->name,
                        'شماره تماس' => $customer->phone,
                        'آدرس' => $customer->address,
                        'شهر' => $customer->city,
                        'امتیاز' => $customer->points,
                        'تعداد سفارشات تکمیل شده' => $customer->complete_order_count,
                        'مجموع سفارشات تکمیل شده' => number_format($customer->complete_order_total),
                        'تعداد سفارشات در انتظار' => $customer->pending_order_count,
                        'مجموع سفارشات در انتظار' => number_format($customer->pending_order_total),
                        'آخرین سفارش' => $customer->last_order_date ,
                        'تاریخ عضویت' => $customer->created_at ? Jalalian::fromDateTime($customer->created_at)->format('Y/m/d') : null,
                    ];
                });

                return \Maatwebsite\Excel\Facades\Excel::download(
                    new \App\Exports\CustomersReportExport($customers),
                    'customers_report_' . time() . '.xlsx'
                );
            }

            if ($request->boolean('pdf')) {
                ini_set('memory_limit', '1024M');
                ini_set('pcre.backtrack_limit', '5000000');
                set_time_limit(600);

                $customers = $query->get()->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                        'address' => $customer->address,
                        'city' => $customer->city,
                        'points' => $customer->points,
                        'completeOrderCount' => $customer->complete_order_count,
                        'completeOrderTotal' => number_format($customer->complete_order_total),
                        'pendingOrderCount' => $customer->pending_order_count,
                        'pendingOrderTotal' => number_format($customer->pending_order_total),
                        'lastOrderDate' =>$customer->last_order_date,
                        'created_at' => $customer->created_at ? Jalalian::fromDateTime($customer->created_at)->format('Y/m/d') : null,
                    ];
                });

                $mpdf = new \Mpdf\Mpdf([
                    'mode'         => 'utf-8',
                    'format'       => 'A4-L',
                    'default_font' => 'Vazir',
                    'tempDir'      => storage_path('app/temp'),
                ]);

                $mpdf->simpleTables = true;
                $mpdf->packTableData = true;

                $headerHtml = view('customers_pdf_header')->render();
                $mpdf->WriteHTML($headerHtml);

                $chunks = $customers->chunk(10);

                foreach ($chunks as $index => $chunk) {
                    $html = view('customers_pdf_chunk', ['customers' => $chunk])->render();
                    $mpdf->WriteHTML($html);
                }

                $filePath = storage_path('app/public/customers_' . time() . '.pdf');
                $mpdf->Output($filePath, 'F');

                return response()->download($filePath)->deleteFileAfterSend(true);
            }

            return (new Response())->ApiPaginatedResponse(
                $query->paginate(10)
            );
        } catch (\Exception $exception) {
            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    public function residentCustomers(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name' => 'nullable|string',
            'phone' => 'nullable|string',
            'no_order_from' => 'nullable|date',
            'no_order_to'   => 'nullable|date|after_or_equal:no_order_from',
            'month_since_last_purchase' => 'nullable|integer|min:0|max:120',
            'min_points' => 'nullable|integer|min:0',
            'max_points' => 'nullable|integer|min:0|gte:min_points',
            'created_at_from' => 'nullable|date',
            'created_at_to' => 'nullable|date|after_or_equal:created_at_from',
            'food_id' => 'nullable|integer|exists:foods,id',
            'reserve_number' => 'nullable|string',
            'room_number' => 'nullable|string',
            'excel' => 'nullable|boolean',
            'pdf' => 'nullable|boolean',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {

            $query = GuestUser::query()
                ->when($request->name, function ($q) use ($request) {
                    $q->where('GuestName', 'like', '%' . $request->name . '%');
                })
                ->when($request->phone, function ($q) use ($request) {
                    $q->where('Mobile', 'like', '%' . $request->phone . '%');
                })
                ->when($request->reserve_number, function ($q) use ($request) {
                    $q->where('Reserve', 'like', '%' . $request->reserve_number . '%');
                })
                ->when($request->room_number, function ($q) use ($request) {
                    $q->where('Room', 'like', '%' . $request->room_number . '%');
                })
                ->when($request->no_order_from && $request->no_order_to, function ($q) use ($request) {
                    $q->whereDoesntHave('orders', function ($query) use ($request) {
                        $query->whereDate('created_at', '>=', $request->no_order_from)
                            ->whereDate('created_at', '<=', $request->no_order_to);
                    });
                })

                ->when($request->created_at_from, function ($q) use ($request) {
                    $jalaliFrom = \Morilog\Jalali\Jalalian::fromDateTime($request->created_at_from)->format('Y/m/d');
                    $q->where('CDate', '>=', $jalaliFrom);
                })
                ->when($request->created_at_to, function ($q) use ($request) {
                    $jalaliTo = \Morilog\Jalali\Jalalian::fromDateTime($request->created_at_to)->format('Y/m/d');
                    $q->where('CDate', '<=', $jalaliTo);
                })
                ->when($request->min_points !== null, function ($q) use ($request) {
                    $q->whereHas('points', function ($query) use ($request) {
                        $query->havingRaw('SUM(points) >= ?', [$request->min_points]);
                    });
                })
                ->when($request->max_points !== null, function ($q) use ($request) {
                    $q->whereHas('points', function ($query) use ($request) {
                        $query->havingRaw('SUM(points) <= ?', [$request->max_points]);
                    });
                })
                ->when($request->month_since_last_purchase !== null, function ($q) use ($request) {
                    $monthsAgo = now()->subMonths($request->month_since_last_purchase);
                    $q->whereHas('orders', function ($query) use ($monthsAgo) {
                        $query->where('created_at', '<=', $monthsAgo)
                            ->whereNotExists(function ($subQuery) use ($monthsAgo) {
                                $subQuery->selectRaw(1)
                                    ->from('orders as o2')
                                    ->whereColumn('o2.reserve_number', 'orders.reserve_number')
                                    ->where('o2.created_at', '>', $monthsAgo);
                            });
                    });
                })
                ->when($request->food_id, function ($q) use ($request) {
                    $q->whereHas('orders.children', function ($query) use ($request) {
                        $query->where('food_id', $request->food_id);
                    });
                })
                ->orderBy('CDate', 'desc');

            if ($request->excel == true) {
                ini_set('max_execution_time', 600);
                ini_set('memory_limit', '1024M');

                $residents = $query->get()->map(function ($resident) {
                    return [
                        'نام مهمان' => $resident->GuestName ?? '',
                        'کد حساب' => $resident->AccCode ?? '',
                        'اتاق' => $resident->Room ?? '',
                        'شماره رزرو' => $resident->Reserve ?? '',
                        'شماره پروفایل' => $resident->Profile ?? '',
                        'امتیاز' => $resident->total_points,
                        'مجموع خرید' => number_format($resident->total_purchased),
                        'آژانس' => $resident->agency ?? '',
                        'شرکت' => $resident->company ?? '',
                        'منبع' => $resident->source ?? '',
                        'گروه' => $resident->group ?? '',
                        'تاریخ ورود' => $resident->Arrival ?? '',
                        'تاریخ خروج' => $resident->departure ?? '',
                        'تاریخ چک‌این' => $resident->CiDate ?? '',
                        'تاریخ چک‌اوت' => $resident->CoDate ?? '',
                        'نرخ' => $resident->Rate ?? '',
                        'موبایل' => $resident->Mobile ?? '',
                        'موجودی' => number_format($resident->balance ?? 0),
                        'یادداشت' => $resident->Note ?? '',
                        'تاریخ ایجاد' => $resident->CDate ?? '',
                    ];
                });

                return \Maatwebsite\Excel\Facades\Excel::download(
                    new \App\Exports\ResidentsReportExport($residents),
                    'residents_report_' . time() . '.xlsx'
                );
            }

            if ($request->pdf == true) {
                ini_set('memory_limit', '1024M');
                ini_set('pcre.backtrack_limit', '5000000');
                set_time_limit(600);

                $residents = $query->get()->map(function ($resident) {
                    return [
                        'GuestName' => $resident->GuestName ?? '',
                        'AccCode' => $resident->AccCode ?? '',
                        'Room' => $resident->Room ?? '',
                        'Reserve' => $resident->Reserve ?? '',
                        'Profile' => $resident->Profile ?? '',
                        'points' => $resident->total_points,
                        'total_purchased' => number_format($resident->total_purchased),
                        'Arrival' => $resident->Arrival ?? '',
                        'departure' => $resident->departure ?? '',
                        'Mobile' => $resident->Mobile ?? '',
                        'balance' => number_format($resident->balance ?? 0),
                        'CDate' => $resident->CDate ?? '',
                    ];
                });

                $mpdf = new \Mpdf\Mpdf([
                    'mode'         => 'utf-8',
                    'format'       => 'A4-L',
                    'default_font' => 'Vazir',
                    'tempDir'      => storage_path('app/temp'),
                ]);

                $mpdf->simpleTables = true;
                $mpdf->packTableData = true;

                $headerHtml = view('residents_pdf_header')->render();
                $mpdf->WriteHTML($headerHtml);

                $chunks = $residents->chunk(10);

                foreach ($chunks as $index => $chunk) {
                    $html = view('residents_pdf_chunk', ['residents' => $chunk])->render();
                    $mpdf->WriteHTML($html);
                }

                $filePath = storage_path('app/public/residents_' . time() . '.pdf');
                $mpdf->Output($filePath, 'F');

                return response()->download($filePath)->deleteFileAfterSend(true);
            }

            if (!empty($request->paginate)) {
                return (new Response())->ApiPaginatedResponse(
                    $query->paginate($request->paginate)
                );
            }

            return (new Response())->ApiResponse([
                'status' => 200,
                'message' => 'عملیات با موفقیت انجام شد.',
                'items' => $query->get(),
            ]);
        } catch (\Exception $exception) {
            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    public function discountUsedGlobal(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name' => 'nullable|string',
            'created_at_from' => 'nullable|date',
            'created_at_to' => 'nullable|date|after_or_equal:created_at_from',
            'order_by' => 'nullable|in:created_at,usage,id',
            'order_direction' => 'nullable|in:asc,desc',
            'excel' => 'nullable|boolean',
            'pdf' => 'nullable|boolean',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $orderBy = $request->order_by ?? 'usage_count';
            $orderDirection = $request->order_direction ?? 'desc';

            $query = Discount::query()
                ->where('scope', 'global')
                ->where('usage_count', '>', 0)
                ->when($request->name, function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->name . '%');
                })
                ->when($request->created_at_from, function ($q) use ($request) {
                    $q->whereDate('created_at', '>=', $request->created_at_from);
                })
                ->when($request->created_at_to, function ($q) use ($request) {
                    $q->whereDate('created_at', '<=', $request->created_at_to);
                })
                ->orderBy($orderBy, $orderDirection);

            if ($request->excel == true) {
                ini_set('max_execution_time', 600);
                ini_set('memory_limit', '1024M');

                $discounts = $query->get()->map(function ($discount) {
                    return [
                        'شناسه' => $discount->id,
                        'نام تخفیف' => $discount->name,
                        'کد تخفیف' => $discount->code,
                        'مقدار تخفیف' => number_format($discount->discount_value),
                        'نوع تخفیف' => $discount->discount_type == 'percentage' ? 'درصدی' : 'ثابت',
                        'تعداد استفاده' => $discount->usage_count,
                        'مدیر سود' => $discount->profitManager ? $discount->profitManager->name : '-',
                        'وضعیت' => $discount->is_active ? 'فعال' : 'غیرفعال',
                        'تاریخ شروع' => $discount->starts_at ? Jalalian::fromDateTime($discount->starts_at)->format('Y/m/d') : 'نامحدود',
                        'تاریخ پایان' =>  $discount->expires_at ? Jalalian::fromDateTime($discount->expires_at)->format('Y/m/d') :  'نامحدود',
                        'تاریخ ایجاد' => $discount->created_at ? Jalalian::fromDateTime($discount->created_at)->format('Y/m/d') :  null,
                    ];
                });

                return \Maatwebsite\Excel\Facades\Excel::download(
                    new \App\Exports\DiscountUsedGlobalReportExport($discounts),
                    'discount_used_global_report_' . time() . '.xlsx'
                );
            }

            if ($request->pdf == true) {
                ini_set('memory_limit', '1024M');
                ini_set('pcre.backtrack_limit', '5000000');
                set_time_limit(600);

                $discounts = $query->get()->map(function ($discount) {
                    return [
                        'id' => $discount->id,
                        'name' => $discount->name,
                        'code' => $discount->code,
                        'discount_value' => number_format($discount->discount_value),
                        'discount_type' => $discount->discount_type == 'percentage' ? 'درصدی' : 'ثابت',
                        'usage' => $discount->usage_count,
                        'is_active' => $discount->is_active ? 'فعال' : 'غیرفعال',
                        'starts_at' => $discount->starts_at ? Jalalian::fromDateTime($discount->starts_at)->format('Y/m/d') : 'نامحدود',
                        'expires_at' => $discount->expires_at ? Jalalian::fromDateTime($discount->expires_at)->format('Y/m/d') :  'نامحدود',
                        'created_at' => $discount->created_at ? Jalalian::fromDateTime($discount->created_at)->format('Y/m/d') :  null,
                    ];
                });

                $mpdf = new \Mpdf\Mpdf([
                    'mode'         => 'utf-8',
                    'format'       => 'A4-L',
                    'default_font' => 'Vazir',
                    'tempDir'      => storage_path('app/temp'),
                ]);

                $mpdf->simpleTables = true;
                $mpdf->packTableData = true;

                $headerHtml = view('discount_used_global_pdf_header')->render();
                $mpdf->WriteHTML($headerHtml);

                $chunks = $discounts->chunk(15);

                foreach ($chunks as $index => $chunk) {
                    $html = view('discount_used_global_pdf_chunk', ['discounts' => $chunk])->render();
                    $mpdf->WriteHTML($html);
                }

                $filePath = storage_path('app/public/discount_used_global_' . time() . '.pdf');
                $mpdf->Output($filePath, 'F');

                return response()->download($filePath)->deleteFileAfterSend(true);
            }

            return (new Response())->ApiPaginatedResponse(
                $query->paginate(10)
            );
        } catch (\Exception $exception) {
            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    public function discountUsedNormal(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'created_at_from' => 'nullable|date',
            'created_at_to' => 'nullable|date|after_or_equal:created_at_from',
            'order_by' => 'nullable|in:created_at,usage,id',
            'order_direction' => 'nullable|in:asc,desc',
            'excel' => 'nullable|boolean',
            'pdf' => 'nullable|boolean',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $orderBy = $request->order_by ?? 'usage_count';
            $orderDirection = $request->order_direction ?? 'desc';

            $query = Discount::query()
                ->where('scope', 'normal')
                ->where('usage_count', '>', 0)
                ->when($request->name, function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->name . '%');
                })
                ->when($request->customer_name, function ($q) use ($request) {
                    $q->where(function ($query) use ($request) {
                        $query->whereHas('customer', function ($subQuery) use ($request) {
                            $subQuery->where('name', 'like', '%' . $request->customer_name . '%');
                        })->orWhereHas('reserve', function ($subQuery) use ($request) {
                            $subQuery->where('GuestName', 'like', '%' . $request->customer_name . '%');
                        });
                    });
                })
                ->when($request->customer_phone, function ($q) use ($request) {
                    $q->where(function ($query) use ($request) {
                        $query->whereHas('customer', function ($subQuery) use ($request) {
                            $subQuery->where('phone', 'like', '%' . $request->customer_phone . '%');
                        })->orWhereHas('reserve', function ($subQuery) use ($request) {
                            $subQuery->where('Mobile', 'like', '%' . $request->customer_phone . '%');
                        });
                    });
                })
                ->when($request->created_at_from, function ($q) use ($request) {
                    $q->whereDate('created_at', '>=', $request->created_at_from);
                })
                ->when($request->created_at_to, function ($q) use ($request) {
                    $q->whereDate('created_at', '<=', $request->created_at_to);
                })
                ->orderBy($orderBy, $orderDirection);

            if ($request->excel == true) {
                ini_set('max_execution_time', 600);
                ini_set('memory_limit', '1024M');

                $discounts = $query->get()->map(function ($discount) {
                    return [
                        'شناسه' => $discount->id,
                        'نام تخفیف' => $discount->name,
                        'کد تخفیف' => $discount->code,
                        'مقدار تخفیف' => number_format($discount->discount_value),
                        'نوع تخفیف' => $discount->discount_type == 'percentage' ? 'درصدی' : 'ثابت',
                        'تعداد استفاده' => $discount->usage,
                        'مشتری' => $discount->customer ? $discount->customer->name : ($discount->reserve ? $discount->reserve->GuestName : '-'),
                        'شماره تماس' => $discount->customer ? $discount->customer->phone : ($discount->reserve ? $discount->reserve->Mobile : '-'),
                        'شماره رزرو' => $discount->reserve_number ?? '-',
                        'وضعیت' => $discount->is_active ? 'فعال' : 'غیرفعال',
                        'تاریخ شروع' => $discount->starts_at ? Jalalian::fromDateTime($discount->starts_at)->format('Y/m/d') : 'نامحدود',
                        'تاریخ پایان' =>  $discount->expires_at ? Jalalian::fromDateTime($discount->expires_at)->format('Y/m/d') :  'نامحدود',
                        'تاریخ ایجاد' => $discount->created_at ? Jalalian::fromDateTime($discount->created_at)->format('Y/m/d') :  null,
                    ];
                });

                return \Maatwebsite\Excel\Facades\Excel::download(
                    new \App\Exports\DiscountUsedNormalReportExport($discounts),
                    'discount_used_normal_report_' . time() . '.xlsx'
                );
            }

            if ($request->pdf == true) {
                ini_set('memory_limit', '1024M');
                ini_set('pcre.backtrack_limit', '5000000');
                set_time_limit(600);

                $discounts = $query->get()->map(function ($discount) {
                    return [
                        'id' => $discount->id,
                        'name' => $discount->name,
                        'code' => $discount->code,
                        'discount_value' => number_format($discount->discount_value),
                        'discount_type' => $discount->discount_type == 'percentage' ? 'درصدی' : 'ثابت',
                        'usage' => $discount->usage,
                        'customer' => $discount->customer ? $discount->customer->name : ($discount->reserve ? $discount->reserve->GuestName : '-'),
                        'customer_phone' => $discount->customer ? $discount->customer->phone : ($discount->reserve ? $discount->reserve->Mobile : '-'),
                        'reserve_number' => $discount->reserve_number ?? '-',
                        'is_active' => $discount->is_active ? 'فعال' : 'غیرفعال',
                        'starts_at' => $discount->starts_at ? Jalalian::fromDateTime($discount->starts_at)->format('Y/m/d') : 'نامحدود',
                        'expires_at' => $discount->expires_at ? Jalalian::fromDateTime($discount->expires_at)->format('Y/m/d') :  'نامحدود',
                        'created_at' => $discount->created_at ? Jalalian::fromDateTime($discount->created_at)->format('Y/m/d') :  null,                    ];
                });

                $mpdf = new \Mpdf\Mpdf([
                    'mode'         => 'utf-8',
                    'format'       => 'A4-L',
                    'default_font' => 'Vazir',
                    'tempDir'      => storage_path('app/temp'),
                ]);

                $mpdf->simpleTables = true;
                $mpdf->packTableData = true;

                $headerHtml = view('discount_used_normal_pdf_header')->render();
                $mpdf->WriteHTML($headerHtml);

                $chunks = $discounts->chunk(15);

                foreach ($chunks as $index => $chunk) {
                    $html = view('discount_used_normal_pdf_chunk', ['discounts' => $chunk])->render();
                    $mpdf->WriteHTML($html);
                }

                $filePath = storage_path('app/public/discount_used_normal_' . time() . '.pdf');
                $mpdf->Output($filePath, 'F');

                return response()->download($filePath)->deleteFileAfterSend(true);
            }
            return (new Response())->ApiPaginatedResponse(
                $query->paginate(10)
            );
        } catch (\Exception $exception) {
            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}