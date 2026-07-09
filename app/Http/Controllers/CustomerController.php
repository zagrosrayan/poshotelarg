<?php

namespace App\Http\Controllers;

use App\Http\Service\TypeSlug;
use App\Models\Customer;
use App\Models\Log;
use App\Models\Order;
use App\Models\Type;
use App\Service\Response;
use App\Service\validateRequest;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function list(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name' => 'nullable|string',
            'from' => 'nullable|string',
            'to' => 'nullable|string',
            'q' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $customers = Customer::query()
                ->when($request->from, function ($query) use ($request) {
                    $query->whereHas('orders', function ($query) use ($request) {
                        $query->whereDate('created_at', '>=', $request->from);
                    });
                })
                ->when($request->to, function ($query) use ($request) {
                    $query->whereHas('orders', function ($query) use ($request) {
                        $query->whereDate('created_at', '<=', $request->to);
                    });
                })
                ->when($request->name, function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->name . '%');
                })
                ->when($request->phone, function ($query) use ($request) {
                    $query->where('phone', 'like', '%' . $request->phone . '%');
                })
                ->when($request->q, function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->q . '%')
                    ->orWhere('phone','like', '%' .$request->q .'%');
                })
                ->orderBy('created_at', 'desc')
                ->paginate();

            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Customer::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست مشتریان با موفقیت انجام شد',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);

            return (new Response())->ApiPaginatedResponse($customers);

        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Customer::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست مشتریان با خطا مواجه شد',
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
}