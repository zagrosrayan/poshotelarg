<?php

namespace App\Http\Controllers;

use App\Models\NextPurchaseDiscount;
use App\Service\Response;
use App\Service\validateRequest;
use Illuminate\Http\Request;

class NextPurchaseDiscountController extends Controller
{
    public function index(Request $request)
    {
        try {
            $settings = NextPurchaseDiscount::query()->where('is_active',true);


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
            'reminder_days_before_expiration' => 'nullable|integer|min:0',
            'discount_validity_days' => 'nullable|integer|min:1',
            'discount_sms_template' => 'nullable|string',
            'reminder_sms_template' => 'nullable|string',
            'profit_manager_ids' => 'nullable|array',
            'profit_manager_ids.*' => 'integer|exists:profit_managers,id',
            'target_customer_types' => 'nullable|array',
            'target_customer_types.*' => 'string|in:resident,Non_resident',
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
                'reminder_days_before_expiration',
                'discount_validity_days',
                'discount_sms_template',
                'reminder_sms_template',
                'profit_manager_ids',
                'target_customer_types',
            ]);
            $data['is_active'] = true;

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