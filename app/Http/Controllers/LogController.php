<?php

namespace App\Http\Controllers;

use App\Http\Service\TypeSlug;
use App\Models\Article;
use App\Models\Log;
use App\Models\Type;
use App\Service\Response;
use App\Service\validateRequest;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function list(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'status' => 'nullable|exists:types,id',
            'operation' => 'nullable|exists:types,id',
            'loggable_type' => 'nullable|string|max:255',
            'loggable_id' => 'nullable|exists:logs,id',
            'date' => 'nullable|date',
            'ip' => 'nullable|ip',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }


        try {
            $logs = Log::query()->with(['user', 'statusType', 'operationType', ])
                ->when($request->user_id, function ($query) use ($request) {
                    $query->where('user_id', $request->user_id);
                })
                ->when($request->status, function ($query) use ($request) {
                    $query->where('status', $request->status);
                })
                ->when($request->operation, function ($query) use ($request) {
                    $query->where('operation', $request->operation);
                })
                ->when($request->loggable_type, function ($query) use ($request) {
                    $query->where('loggable_type', 'like', '%' . $request->loggable_type . '%');
                })
                ->when($request->loggable_id, function ($query) use ($request) {
                    $query->where('loggable_id', $request->loggable_id);
                })
                ->when($request->date, function ($query) use ($request) {
                    $query->whereDate('date', '=', $request->date);
                })
                ->when($request->ip, function ($query) use ($request) {
                    $query->where('ip', $request->ip);
                })
                ->paginate(10);


            // Log success operation
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Article::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست لاگ ها با موفقیت انجام شد.',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id,
            ]);

            // Return success response
            return (new Response())->ApiPaginatedResponse(
                $logs
            );
        } catch (\Exception $exception) {
            // Log failed operation
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Article::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست لاگ هابا خطا مواجه شد.',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_FAILED)->first()->id,
            ]);

            // Return error response
            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ]);
        }
    }
}
