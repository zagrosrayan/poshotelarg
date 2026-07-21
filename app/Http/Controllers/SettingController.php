<?php

namespace App\Http\Controllers;

use App\Http\Service\TypeSlug;
use App\Models\Log;
use App\Models\Setting;
use App\Models\Type;
use App\Service\Response;
use App\Service\validateRequest;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $setting = Setting::first();
        return (new Response())->ApiResponse([
            'status' => 200,
            'message' => 'عملیات با موفقیت انجام شد.',
            'items' => $setting,
        ]);
    }

    public function update(Request $request,Setting $setting)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'tax' => 'required|numeric|max:255',
            'rate_service' => 'required|numeric|min:0|max:100', // درصد تخفیف
        ]);
        if ($validationResult !== true) {
            return $validationResult;
        }
        try {
            $data = $request->only([
                'tax',
                'rate_service',
            ]);

            $setting->update($data);
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Setting::class,
                'loggable_id' => $setting->id,
                'message' => 'عملیات اپدیت تنظیمات با موفقیت انجام شد.',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id,
            ]);
            return (new Response())->ApiResponse([
                'status' => 200,
                'message' => 'عملیات با موفقیت انجام شد.',
                'items' => $setting,
            ]);
        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Setting::class,
                'loggable_id' => null,
                'message' => 'عملیات اپدیت تنظیمات با خطا مواجه شد.',
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
}
