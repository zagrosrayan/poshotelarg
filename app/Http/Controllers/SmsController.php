<?php

namespace App\Http\Controllers;

use App\Service\Response;
use App\Service\SmsService;
use App\Service\validateRequest;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function sendBulk(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'phones' => 'required|array|min:1',
            'phones.*' => 'required|string|regex:/^09[0-9]{9}$/',
            'text' => 'required|string|max:500',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $smsService = new SmsService();
            $results = $smsService->send($request->phones, $request->text);

            $successCount = collect($results)->where('status', 'success')->count();
            $failedCount = collect($results)->where('status', 'failed')->count();

            return (new Response())->ApiResponse([
                'message' => 'عملیات ارسال پیام انجام شد',
                'items' => [
                    'total' => count($results),
                    'success' => $successCount,
                    'failed' => $failedCount,
                    'details' => $results
                ]
            ]);

        } catch (\Exception $exception) {
            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    public function sendSingle(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
            'text' => 'required|string|max:500',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $smsService = new SmsService();
            $results = $smsService->send([$request->phone], $request->text);

            return (new Response())->ApiResponse([
                'message' => 'پیام با موفقیت ارسال شد',
                'items' => $results[0]
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