<?php

namespace App\Http\Controllers\API\V1_1;

use App\Http\Controllers\Controller;
use App\Http\Resources;
use App\Models\Theme;
use Mews\Purifier\Facades\Purifier;
use Validator;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ThemeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $theme= Theme::all();
        return response()->json(["themes"=>Resources\ThemeResource::collection($theme)],200);
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
            "title"    =>  "required",
            "year"     =>  "required",
        ]);

        if ($validator->fails()){
            return response()->json(["message"=>"Title and year attributes required"],400);
        }

        $theme=new Theme([
            "title"         =>  $request->title,
            "slug"          =>  Str::slug($request->title." ".$request->year),
            "year"          =>  $request->year,
            "description"   =>  Purifier::clean($request->description),
        ]);

        $theme->save();

        return response()->json(["theme"=>new Resources\ThemeResource($theme)],200);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($slug)
    {
        $theme = Theme::where('slug','=',$slug)->first();
        if (!is_object($theme))
            return response()->json(["response"=>false],204);
        else
            return response()->json(["theme"=>new Resources\ThemeResource($theme)],200);
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
        $theme = Theme::where('slug','=',$slug)->first();
        if (!is_object($theme))
            return response()->json(["response"=>false],204);
        else {
            $theme->update([
                "title"         =>  $request->title,
                "slug"          =>  Str::slug($request->title." ".$request->year),
                "year"          =>  $request->year,
                "description"   =>  Purifier::clean($request->description),
            ]);
            return response()->json(["theme"=>new Resources\ThemeResource($theme)],200);
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
        $theme = Theme::where('slug','=',$slug)->first();
        if (!is_object($theme))
            return response()->json(["response"=>false],204);
        else {
            $theme->delete();
            return response()->json(["response" => true], 200);
        }
    }
}
