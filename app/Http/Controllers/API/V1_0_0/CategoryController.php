<?php

namespace App\Http\Controllers\API\V1_0_0;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Resources;
use Illuminate\Support\Str;
use Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $category= Category::all();
        return response()->json(["category"=>Resources\CategoryResource::collection($category)],200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator=Validator::make($request->all(),[
            "name"     =>  "required",
        ]);

        if ($validator->fails()){
            return response()->json(["message"=>"Name attribute required"],400);
        }

        $category=new Category([
            "name"  =>  $request->name,
            "slug"  =>  Str::slug($request->name).date("-Y-m-d")
        ]);

        $category->save();

        return response()->json(["category"=>new Resources\CategoryResource($category)],200);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($slug)
    {
        $category = Category::where('slug','=',$slug)->first();
        if (!is_object($category))
            return response()->json(["response"=>false],204);
        else
            return response()->json(["category"=>new Resources\CategoryResource($category)],200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $slug)
    {
        $category = Category::where('slug','=',$slug)->first();
        if (!is_object($category))
            return response()->json(["response"=>false],204);
        else {
            $category->update([
                "name"  =>  $request->name,
                "slug"  =>  Str::slug($request->name).date("-Y-m-d")
            ]);
            return response()->json(["category"=>new Resources\CategoryResource($category)],200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($slug)
    {
        $category = Category::where('slug','=',$slug)->first();
        if (!is_object($category))
            return response()->json(["response"=>false],204);
        else {
            $category->delete();
            return response()->json(["response" => true], 200);
        }
    }
}
