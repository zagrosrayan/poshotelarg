<?php

namespace App\Http\Controllers;

use App\Http\Service\TypeSlug;
use App\Models\Log;
use App\Models\ProfitManager;
use App\Models\Type;
use App\Service\Response;
use App\Service\validateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProfitManagerController extends Controller
{
    public function list(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name' => 'nullable|string',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $profitManagers = ProfitManager::query()->where('name', 'like', '%' . $request->name . '%')->with(['status'])->get();
                Log::query()->create([
                    'user_id' => $request->user()->id,
                    'ip' => $request->ip(),
                    'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                    'loggable_type' => ProfitManager::class,
                    'loggable_id' => null,
                    'message' => 'عملیات لیست مرکز درامد با موفقیت آمیز بود',
                    'date' => now(),
                    'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id,
                ]);

                return (new Response())->ApiResponse([
                    'status' => 200,
                    'message' => 'عملیات موفقیت آمیز بود.',
                    'items' => $profitManagers
                ]);

        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => ProfitManager::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست مرکز درامد با خطا مواجه شد',
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

    public function create(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name'  => 'required|string',
            'slug'  => 'required|string|unique:profit_managers,slug',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $profitManager = ProfitManager::query()->create([
                'name' => $request->name,
                'slug' => $request->slug,
            ]);

            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => ProfitManager::class,
                'loggable_id' => $profitManager->id,
                'message' => 'عملیات ایجاد  مرکز درامد با موفقیت انجام شد',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);
            return (new Response())->ApiResponse([
                $profitManager,
                'message' => 'عملیات موفقیت آمیز بود'
            ]);
        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => ProfitManager::class,
                'loggable_id' => null,
                'message' => 'عملیات ایجاد  مرکز درامد با خطا مواجه شد',
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

    public function update(Request $request, ProfitManager $profitManager)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name'  => 'nullable|string',
            'slug'  => 'nullable|string|unique:profit_managers,slug,' . $profitManager->id,
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $profitManager->update([
                'name' => $request->name,
                'slug' => $request->slug,
            ]);

            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_UPDATE)->first()->id,
                'loggable_type' => ProfitManager::class,
                'loggable_id' => $profitManager->id,
                'message' => 'عملیات بروزرسانی مرکز درامد با موفقیت انجام شد',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);

            return (new Response())->ApiResponse([
                $profitManager,
                'message' => 'عملیات بروزرسانی موفقیت آمیز بود'
            ]);
        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_UPDATE)->first()->id,
                'loggable_type' => ProfitManager::class,
                'loggable_id' => null,
                'message' => 'عملیات بروزرسانی  مرکز درامد با خطا مواجه شد',
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
