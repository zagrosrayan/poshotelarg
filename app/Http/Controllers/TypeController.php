<?php

namespace App\Http\Controllers;

use App\Models\Printer;
use App\Models\Type;
use App\Service\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $type = Type::query()->when($request->slug, function ($query) use ($request) {
            $query->where('slug',$request->slug);
        })->when($request->category,function ($query) use ($request){
            $query->where('category',$request->category);
        });
        if ($request->slug){
            return (new Response())->ApiResponse([
                'message' => 'عملیات با موفقیت انجام شد.',
               'items' => $type->first()
            ]);
        }
            return (new Response())->ApiResponse([
                'message' => 'عملیات با موفقیت انجام شد.',
               'items' => $type->get()
            ]);


    }

    public function store(Request $request)
    {
        $slug = Str::slug($request->name);
        $type = Type::query()->where('slug', $slug)->where('category',$request->category)->first();
        if (!$type){
            Type::query()->create(
                [
                    'name' => $request->name,
                    'slug' => $slug . '-' . Str::random(5),
                    'category' => $request->category
                ]
            );
            return (new Response())->ApiResponse([
                'message' => 'عملیات با موفقیت انجام شد.',
               'items'=> $type
            ]);
        }
        return (new Response())->ApiResponse([
            'message' => 'قبلا این مورد را ایجاد کردید',
           'items'=> $type
        ]);
    }

}
