<?php

namespace App\Http\Controllers;

use App\Http\Service\TypeSlug;
use App\Models\Log;
use App\Models\Type;
use App\Models\User;
use App\Models\UserList;
use App\Service\Response;
use App\Service\validateRequest;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {

            $user = User::query()->where('username', $request->username)->with(['profitManager','roles','roles.permissions'])->first();
            if (!empty($user) && $request->password == $user->password) {
                $token = $user->createToken(env('SANCTUM_API'))->plainTextToken;

                Log::query()->create([
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_LOGIN)->first()->id,
                    'loggable_type' => User::class,
                    'loggable_id' => $user->id,
                    'message' => 'عملیات ورود به سیستم موفقیت آمیز بود',
                    'date' => now(),
                    'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id,
                ]);

                return (new Response())->ApiResponse([
                    'status' => 200,
                    'message' => 'ورود موفقیت آمیز بود.',
                    'items' => [
                        'user' => $user,
                        'token' => $token,
                    ],
                ]);
            }

            Log::query()->create([
                'user_id' => $user ? $user->id : null,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_LOGIN)->first()->id,
                'loggable_type' => User::class,
                'loggable_id' => $user ? $user->id : null,
                'message' => 'عملیات ورود به سیستم به دلیل اشتباه بودن نام کاربری یا رمز عبور با خطا مواجه شد',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_FAILED)->first()->id,
            ]);

            return (new Response())->ApiResponse([
                'status' => 403,
                'message' => 'نام کاربری یا رمز عبور اشتباه است.',
            ]);
        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => null,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_LOGIN)->first()->id,
                'loggable_type' => User::class,
                'loggable_id' => null,
                'message' => 'عملیات ورود به سیستم با خطا مواجه شد',
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
    public function resetPassword(Request $request){
        $validationResult = (new validateRequest())->validate($request->all(), [
            'old_password' => 'required|string',
            'password' => 'required|string|confirmed',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            if (password_verify($request->old_password,$request->user()->password)){
                 User::query()->where('username', $request->user()->username)->update([
                    'password' => Hash::make($request->password)
                ]);
                Log::query()->create([
                    'user_id' => $request->user()->id,
                    'ip' => $request->ip(),
                    'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_UPDATE)->first()->id,
                    'loggable_type' => User::class,
                    'loggable_id' => $request->user()->id,
                    'message' => 'عملیات تغییر رمز عبور موفقیت امیز بود',
                    'date' => now(),
                    'status' =>   Type::query()->where('slug',TypeSlug::LOG_STATUS_SUCCESS)->first()->id
                ]);
                return (new Response())->ApiResponse([
                    'items' => [
                        'user' => User::query()->with(['profitManager'])->find($request->user()->id),
                    ],
                ]);
            }
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_UPDATE)->first()->id,
                'loggable_type' => User::class,
                'loggable_id' => $request->user()->id,
                'message' => 'عملیات تغییر رمز عبور به دلیل اشتباه بودن رمز با خطا مواجه شد',
                'date' => now(),
                'status' =>   Type::query()->where('slug',TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);
            return (new Response())->ApiResponse([
                'message' => 'رمز عبور اشتباه است',
                'status' => 403,
            ]);
        }catch (\Exception $exception){
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_UPDATE)->first()->id,
                'loggable_type' => User::class,
                'loggable_id' => $request->user()->id,
                'message' => 'عملیات تغییر رمز عبور با خطا مواجه شد',
                'date' => now(),
                'status' =>   Type::query()->where('slug',TypeSlug::LOG_STATUS_SUCCESS)->first()->id
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
