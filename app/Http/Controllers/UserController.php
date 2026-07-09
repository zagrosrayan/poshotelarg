<?php

namespace App\Http\Controllers;

use App\Http\Service\TypeSlug;
use App\Models\Article;
use App\Models\GuestUser;
use App\Models\Log;
use App\Models\Type;
use App\Models\User;
use App\Service\Response;
use App\Service\validateRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{

    public function roleList(Request $request)
    {
        try {
            $roles =  Role::with(['permissions'])->get();

            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Role::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست نقش با موفقیت انجام شد',
                'date' => now(),
                'status' =>   Type::query()->where('slug',TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);
            return (new Response())->ApiResponse(
                [
                    'items' => $roles,
                ]
            );
        }catch (\Exception $exception){
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Role::class,
                'loggable_id' => null,
                'message' => 'عملیات  لیست نقش با خطا مواجه شد',
                'date' => now(),
                'status' =>   Type::query()->where('slug',TypeSlug::LOG_STATUS_FAILED)->first()->id
            ]);
            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ]);
        }
    }
    public function assign(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'users' => 'required|array',
            'role'  => 'required|string',
            'profit_manager_id'  => 'required|string|exists:profit_managers,id',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $role = $request->role;

            if (!$role || !Role::where('name', $role)->exists()) {
                return response()->json([
                    'status' => 400,
                    'message' => 'نقش معتبر نیست.'
                ]);
            }

            foreach ($request->users as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->syncRoles([$role]);
                }
                if ($request->profit_manager_id){
                    $user->update([
                        'profit_manager_id' => $request->profit_manager_id
                    ]);
                }
            }

            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Role::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست نقش با موفقیت انجام شد',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id,
            ]);

            return (new Response())->ApiResponse([
                'status' => 200,
                'message' => 'نقش با موفقیت به کاربران اختصاص یافت.',
            ]);

        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Role::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست نقش با خطا مواجه شد',
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
    public function guestList(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name'        => 'nullable|string',
            'reserve_number' => 'nullable|string',
        ]);
        if ($validationResult !== true) {
            return $validationResult;
        }
        try {
            $guest = GuestUser::query()->when($request->name, function ($query) use ($request) {
                $query->where('GuestName', 'like', '%' . $request->name . '%');
            })->when($request->reserve_number, function ($query) use ($request) {
                $query->where('Room', $request->reserve_number);
            })->paginate(10);
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => GuestUser::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست کاربران مقیم هتل با موفقیت انجام شد',
                'date' => now(),
                'status' =>   Type::query()->where('slug',TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);
            return (new Response())->ApiPaginatedResponse(
                $guest
            );
        }catch (\Exception $exception){
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => GuestUser::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست کاربران مقیم هتل با خطا مواجه شد',
                'date' => now(),
                'status' =>   Type::query()->where('slug',TypeSlug::LOG_STATUS_FAILED)->first()->id
            ]);
            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ]);
        }

    }

    public function list(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name'        => 'nullable|string',
            "q" => "nullable|string",
        ]);
        if ($validationResult !== true) {
            return $validationResult;
        }
        try {
            $user = User::query()->when($request->name, function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->name . '%');
            })->with(['roles','permissions','profitManager'])->paginate(10);
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => User::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست کاربران  با موفقیت انجام شد',
                'date' => now(),
                'status' =>   Type::query()->where('slug',TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);
            return (new Response())->ApiPaginatedResponse(
                $user
            );
        }catch (\Exception $exception){
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug',TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => GuestUser::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست کاربران با خطا مواجه شد',
                'date' => now(),
                'status' =>   Type::query()->where('slug',TypeSlug::LOG_STATUS_FAILED)->first()->id
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
