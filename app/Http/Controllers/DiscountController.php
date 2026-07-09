<?php
namespace App\Http\Controllers;

use App\Http\Service\TypeSlug;
use App\Models\Article;
use App\Models\Discount;
use App\Models\Log;
use App\Models\Order;
use App\Models\Type;
use App\Repository\Discount\Contracts\DiscountApplierInterface;
use App\Repository\Discount\Contracts\DiscountCreatorInterface;
use App\Repository\Discount\DTO\DiscountCreateDTO;
use App\Repository\Discount\DTO\DiscountUpdateDTO;
use App\Service\DiscountService;
use App\Service\Response;
use App\Service\validateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DiscountController extends Controller
{
    public function __construct(
        private DiscountCreatorInterface $discountCreator,
    ) {}
    public function store(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name' => 'required|string|max:255',
            'discount_value' => [
                'required',
                'numeric',
                'min:0',
            ],
            'code' => ['nullable', 'string', Rule::unique('discounts', 'code')],
            'minimum_price' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
            'is_special' => 'required|boolean',
            'customer_id' => 'nullable|numeric|exists:customers,id',
            'profit_manager_ids' => 'nullable|array',
            'profit_manager_ids.*' => 'integer|exists:profit_managers,id',
            'reserve_number' => 'nullable|numeric|exists:InhouseList,Reserve',
            'usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $data = $request->only([
                'name',
                'code',
                'discount_value',
                'minimum_price',
                'is_active',
                'is_special',
                'customer_id',
                'profit_manager_ids',
                'reserve_number',
                'usage_limit',
                'starts_at',
                'expires_at',
                'discount_type'
            ]);
            $data['scope'] = 'normal';
            $data['is_unlimited'] = false;
            $dto = new DiscountCreateDTO($data);
            if (empty($request->code)){
                $dto->setCode($this->generateUniqueDiscountCode());
            }

            $discount = $this->discountCreator->create($dto);

            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Discount::class,
                'loggable_id' => $discount->id,
                'message' => 'عملیات ایجاد تخفیف با موفقیت انجام شد.',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id,
            ]);

            return (new Response())->ApiResponse([
                'status' => 200,
                'message' => 'عملیات با موفقیت انجام شد.',
                'items' => $discount,
            ]);
        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Discount::class,
                'loggable_id' => null,
                'message' => 'عملیات ایجاد تخفیف با خطا مواجه شد.',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_FAILED)->first()->id,
            ]);
            return (new Response())->ApiResponse([
                'status' => 500,
                'message' =>$exception->getMessage() ,
            ]);
        }
    }
    private function generateUniqueDiscountCode()
    {
        do {
            $code = 'DISCOUNT-' . Str::upper(Str::random(10));
        } while (Discount::where('code', $code)->exists());

        return $code;
    }
    public function destroy($id)
    {
        $discount = Discount::findOrFail($id);
        $hasOrders = $discount->orders()->exists();
        if ($hasOrders) {
            return (new Response())->ApiResponse([
                'status' => 400,
                'message' => 'این تخفیف در سفارش‌ها استفاده شده و امکان حذف آن وجود ندارد. در صورت نیاز آن را غیرفعال کنید.',
                'items'=> $discount,
            ]);
        }
        if (!$discount->delete()) {
            return (new Response())->ApiResponse([
                'status' => 400,
                'message' => 'حذف تخفیف با مشکل مواجه شد.',
                'items'=> $discount,
            ]);
        }

        return (new Response())->ApiResponse([
            'status' => 200,
            'message' => 'عملیات با موفقیت انجام شد.',
        ]);
    }
    public function list(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name' => 'nullable|string|max:255',
            'is_special' => 'nullable|boolean',
            'profit_manager_id' => 'nullable|numeric|exists:profit_managers,id',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }
        try {
            $discount = Discount::query();
            $discount->where('scope','normal')
                ->when($request->name,function ($q)use($request){
                $q->where('name','like','%'.$request->name .'%');
            })->when($request->is_special,function ($q)use($request){
                $q->where('is_special',$request->is_special);
            })->when($request->profit_manager_id, function ($q) use ($request) {
                    $q->whereJsonContains('profit_manager_ids', $request->profit_manager_id);
                })
                ->when($request->starts_at && $request->expires_at, function ($q) use($request) {
                    $q->where(function($query) use($request) {
                        $query->whereBetween('starts_at', [$request->starts_at, $request->expires_at])
                            ->orWhereBetween('expires_at', [$request->starts_at, $request->expires_at])
                            ->orWhere(function($q) use($request) {
                                $q->where('starts_at', '<=', $request->starts_at)
                                    ->where('expires_at', '>=', $request->expires_at);
                            });
                    });
                })->orderBy('created_at','DESC')->with(['customer','reserve']);
            return (new Response())->ApiPaginatedResponse(
                $discount->paginate(10)
            );


        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Discount::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست تخفیف با خطا مواجه شد.',
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
    public function indexGlobal(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name' => 'nullable|string|max:255',
            'is_unlimited' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }
        try {
            $discount = Discount::query();
            $discount->where('scope','global')
                ->when($request->is_unlimited !== null,function ($q)use($request){
                    $q->where('is_unlimited',$request->boolean('is_unlimited'));
                })
                ->when($request->name,function ($q)use($request){
                $q->where('name','like','%'.$request->name .'%');
            })->when($request->starts_at && $request->expires_at, function ($q) use($request) {
                    $q->where(function($query) use($request) {
                        $query->whereBetween('starts_at', [$request->starts_at, $request->expires_at])
                            ->orWhereBetween('expires_at', [$request->starts_at, $request->expires_at])
                            ->orWhere(function($q) use($request) {
                                $q->where('starts_at', '<=', $request->starts_at)
                                    ->where('expires_at', '>=', $request->expires_at);
                            });
                    });
                })->orderBy('created_at','DESC')->with(['customer','reserve']);
                return (new Response())->ApiPaginatedResponse(
                    $discount->paginate(10)
                );



        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Discount::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست تخفیف با خطا مواجه شد.',
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

    public function storeGlobal(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'name' => 'required|string|max:255',
            'discount_value' => [
                'required',
                'numeric',
                'min:0',
            ],
            'code' => ['nullable', 'string', Rule::unique('discounts', 'code')],
            'is_unlimited' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $data = $request->only([
                'name',
                'code',
                'discount_value',
                'is_unlimited',
                'is_active',
                'starts_at',
                'expires_at',
            ]);
            $data['scope'] = 'global';
            $data['discount_type'] = 'fixed';
            $dto = new DiscountCreateDTO($data);
            if (empty($request->code)){
                $dto->setCode($this->generateUniqueDiscountCode());
            }

            $discount = $this->discountCreator->create($dto);

            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Discount::class,
                'loggable_id' => $discount->id,
                'message' => 'عملیات ایجاد تخفیف با موفقیت انجام شد.',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_SUCCESS)->first()->id,
            ]);

            return (new Response())->ApiResponse([
                'status' => 200,
                'message' => 'عملیات با موفقیت انجام شد.',
                'items' => $discount,
            ]);
        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Discount::class,
                'loggable_id' => null,
                'message' => 'عملیات ایجاد تخفیف با خطا مواجه شد.',
                'date' => now(),
                'status' => Type::query()->where('slug', TypeSlug::LOG_STATUS_FAILED)->first()->id,
            ]);
            return (new Response())->ApiResponse([
                'status' => 500,
                'message' => 'خطای سیستمی رخ داده است.',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    public function listValid(Request $request)
    {
        $validationResult = (new validateRequest())->validate($request->all(), [
            'scope' => 'required|in:normal,global,next_purchase',
        ]);

        if ($validationResult !== true) {
            return $validationResult;
        }

        try {
            $discount = Discount::query()
                ->where('scope', $request->scope)
                ->where('is_active', true)
                ->where(function($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>=', now());
                })
                ->where(function($q) {
                    $q->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', now());
                })
                ->where(function($q) {
                    $q->where('is_unlimited', true)
                        ->orWhereColumn('usage_count', '<', 'usage_limit')
                        ->orWhereNull('usage_limit');
                })
                ->orderBy('created_at', 'DESC')
            ;

            return (new Response())->ApiPaginatedResponse(
                $discount->paginate(10)
            );

        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_INDEX)->first()->id,
                'loggable_type' => Discount::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست تخفیف‌های معتبر با خطا مواجه شد.',
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

    public function updateStatus(Request $request, Discount $discount)
    {
        try {
            $discount->update([
                'is_active' => !$discount->is_active,
            ]);
            return (new Response())->ApiResponse([
                'status' => 200,
                'message' => 'عملیات با موفقیت انجام شد.',
            ]);

        } catch (\Exception $exception) {
            Log::query()->create([
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'operation' => Type::query()->where('slug', TypeSlug::LOG_OPERATION_CREATE)->first()->id,
                'loggable_type' => Discount::class,
                'loggable_id' => null,
                'message' => 'عملیات لیست تخفیف با خطا مواجه شد.',
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
