<?php

namespace App\Http\Controllers;

use App\Http\Service\TypeSlug;
use App\Models\Article;
use App\Models\Food;
use App\Models\Log;
use App\Models\ProfitManager;
use App\Models\Type;
use App\Service\Response;
use App\Service\validateRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    public function list(Request $request)
    {
        // Validation rules with custom error messages
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name' => 'nullable|string|max:255',
'slug' => 'nullable|string|regex:/^[a-zA-Z0-9\-]+$/|max:100',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }
        try {
            // Query for articles
            $profit_manager_bakery = ProfitManager::query()
                ->where('slug', TypeSlug::PROFIT_MANAGER_TYPE_BAKERY)
                ->first()->id;

            $profit_manager_restaurant = ProfitManager::query()
                ->where('slug', TypeSlug::PROFIT_MANAGER_TYPE_RESTAURANT)
                ->first()->id;

            $articles = Article::query()
                ->with(['parent'])
                ->when($request->input('name'), function ($query, $name) {
                    $query->where('name', 'like', '%' . $name . '%');
                })
                ->when($request->user() && $request->user()->profit_manager_id, function ($query) use ($request, $profit_manager_bakery, $profit_manager_restaurant) {
                    if ($request->user()->hasRole('admin')) {
                        return;
                    }
                    if ($request->user()->profit_manager_id == $profit_manager_bakery) {
                        return;
                    }

                    $query->whereHas('food', function ($query) use ($request, $profit_manager_bakery, $profit_manager_restaurant) {
                        if ($request->user()->profit_manager_id == $profit_manager_restaurant) {
                            $query->whereIn('profit_manager_id', [$request->user()->profit_manager_id, $profit_manager_bakery]);
                        } else {
                            $query->where('profit_manager_id', $request->user()->profit_manager_id);
                        }
                    });
                })
                ->when($request->input('slug'), function ($query, $slug) {
                    $query->where('slug', $slug);
                })
                ->get();

            // Log success operation
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Article::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست دسته‌بندی غذاها با موفقیت انجام شد.',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id,
            ]);

            // Return success response
            return (new Response())->ApiResponse([
                'status' => 200,
                'message' => 'عملیات با موفقیت انجام شد.',
                'items' => $articles,
            ]);
        } catch (\Exception $exception) {
            // Log failed operation
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Article::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست دسته‌بندی غذاها با خطا مواجه شد.',
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

    public function create(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name' => 'required|string',
            'slug' => 'required|string|unique:articles,slug',
            'parent_id' => 'nullable|exists:articles,id',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $article = Article::query()->create([
                'name' => $request->name,
                'slug' => $request->slug,
                'parent_id' => $request->parent_id,
            ]);

            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Article::class,
                'loggable_id' => $article->id,
                'message' => 'عملیات ایجاد دسته بندی غذا با موفقیت انجام شد',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);

            return (new Response())->ApiResponse([
                $article,
                'message' => 'عملیات موفقیت آمیز بود'
            ]);
        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Article::class,
                'loggable_id' => null,
                'message' => 'عملیات ایجاد دسته بندی غذا با خطا مواجه شد',
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

    public function update(Request $request, Article $article)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name' => 'nullable|string',
            'slug' => 'nullable|string|unique:articles,slug,' . $article->id,
            'parent_id' => 'nullable|exists:articles,id',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $article->update([
                'name' => $request->name,
                'slug' => $request->slug,
                'status' => $request->status,
                'image' => $request->image,
                'parent_id' => $request->parent_id,
            ]);

            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_UPDATE)->first()->id,
                'loggable_type' => Article::class,
                'loggable_id' => $article->id,
                'message' => 'عملیات بروزرسانی دسته بندی غذا با موفقیت انجام شد',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id
            ]);

            return (new Response())->ApiResponse([
                $article,
                'message' => 'عملیات بروزرسانی دسته بندی غذا آمیز بود'
            ]);
        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_UPDATE)->first()->id,
                'loggable_type' => Article::class,
                'loggable_id' => null,
                'message' => 'عملیات بروزرسانی دسته بندی غذا با خطا مواجه شد',
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
