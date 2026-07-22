<?php

namespace App\Http\Controllers;

use App\Models\DiscountSmsDelivery;
use App\Models\NextPurchaseDiscount;
use App\Service\Response;
use App\Service\validateRequest;
use Illuminate\Http\Request;

class NextPurchaseDiscountController extends Controller
{
    public function index(Request $request)
    {
        try {
            $settings = NextPurchaseDiscount::query()->where('is_active', true);

            return (new Response())->ApiResponse([
                'status' => 200,
                'item' => $settings->latest()->first(),
            ]);
        } catch (\Exception $exception) {
            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    public function store(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name' => 'required|string|max:255',
            'days' => 'nullable|integer|min:0',
            'minimum_purchase_amount' => 'required|numeric|min:0',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'discount_validity_days' => 'nullable|integer|min:1',
            'profit_manager_ids' => 'nullable|array',
            'profit_manager_ids.*' => 'integer|exists:profit_managers,id',
            'target_customer_types' => 'nullable|array',
            'target_customer_types.*' => 'string|in:resident,Non_resident',
            'sms_enabled' => 'nullable|boolean',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $data = $request->only([
                'name',
                'days',
                'minimum_purchase_amount',
                'discount_percentage',
                'is_active',
                'discount_validity_days',
                'profit_manager_ids',
                'target_customer_types',
                'sms_enabled',
            ]);
            $data['is_active'] = true;
            $data['sms_enabled'] = $request->boolean('sms_enabled', true);

            $setting = NextPurchaseDiscount::create($data);

            return (new Response())->ApiResponse([
                'status' => 200,
                'message' => 'تنظیمات تخفیف خرید بعدی با موفقیت ایجاد شد.',
                'items' => $setting,
            ]);
        } catch (\Exception $exception) {
            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'sms_enabled' => 'required|boolean',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $setting = NextPurchaseDiscount::find($id);

            if (empty($setting)) {
                return (new Response())->ApiResponse([
                    'status' => 404,
                    'message' => 'تنظیماتی با این شناسه یافت نشد.',
                ]);
            }

            $setting->sms_enabled = $request->boolean('sms_enabled');
            $setting->save();

            if (!$setting->sms_enabled) {
                DiscountSmsDelivery::query()
                    ->where('status', DiscountSmsDelivery::STATUS_PENDING)
                    ->update([
                        'status' => DiscountSmsDelivery::STATUS_CANCELLED,
                        'last_response' => [
                            'reason' => 'sms_disabled',
                            'at' => now()->toDateTimeString(),
                        ],
                    ]);
            }

            return (new Response())->ApiResponse([
                'status' => 200,
                'message' => $setting->sms_enabled
                    ? 'ارسال پیامک تخفیف خرید بعدی فعال شد.'
                    : 'ارسال پیامک تخفیف خرید بعدی غیرفعال شد.',
                'items' => $setting,
            ]);
        } catch (\Exception $exception) {
            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $setting = NextPurchaseDiscount::find($id);

            if (empty($setting)) {
                return (new Response())->ApiResponse([
                    'status' => 404,
                    'message' => 'تنظیماتی با این شناسه یافت نشد.',
                ]);
            }

            $setting->delete();

            return (new Response())->ApiResponse([
                'status' => 200,
                'message' => 'تنظیمات تخفیف خرید بعدی با موفقیت حذف شد.',
            ]);
        } catch (\Exception $exception) {
            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
