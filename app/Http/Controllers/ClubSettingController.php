<?php

namespace App\Http\Controllers;

use App\Models\ClubSetting;
use App\Service\Response;
use App\Service\validateRequest;
use App\Jobs\UpgradeCustomersPoints;
use Illuminate\Http\Request;

class ClubSettingController extends Controller
{
    public function index()
    {
        $club_setting = ClubSetting::query()->latest()->first();
        return (new Response())->ApiResponse([
            'status' => 200,
            'message' => 'عملیات با موفقیت انجام شد.',
            'items' => $club_setting,
        ]);
    }

    public function create(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'points_per_purchase' => 'required|integer',
            'amount_per_point' => 'required|integer',
            'points_per_discount' => 'required|integer',
            'discount_amount_per_point' => 'required|integer',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $data = $request->only([
                'points_per_purchase',
                'amount_per_point',
                'points_per_discount',
                'discount_amount_per_point',
            ]);

            $club_setting = ClubSetting::create($data);

            UpgradeCustomersPoints::dispatch()->delay(now()->addSeconds(10));
            return (new Response())->ApiResponse([
                'status' => 200,
                'message' => 'عملیات با موفقیت انجام شد.',
                'items' => $club_setting,
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