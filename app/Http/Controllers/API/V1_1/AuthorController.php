<?php

namespace App\Http\Controllers\API\V1_1;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Mews\Purifier\Facades\Purifier;
use Validator;
use Illuminate\Support\Str;

class AuthorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $authors= Author::all();
        return response()->json(Resources\AuthorResource::collection($authors),200);
    }


    /* Discarded */
    /**
     * Display a listing of the resource.
     *
     * @param string $filter
     * @param string $query
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFiltered($filter,$query)
    {
        switch ($filter){
            case "All":
                $authors= Author::orderBy("name","asc")->get();
                break;
            case "ICA":
                $authors= Author::where("ica_pastor",1)->orderBy("name","asc")->get();
                break;
            case "Other":
                $authors= Author::where("ica_pastor",0)->orderBy("name","asc")->get();
                break;
            case "Trashed":
                $authors= Author::onlyTrashed()->orderBy("name","asc")->get();
                break;
            case "Search":
                $authors=Author::search($query)->withTrashed()->get();
                break;
            default:
                return response()->json([],204);
        }
        return response()->json(Resources\AuthorResource::collection($authors),200);
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
            "name"          =>  "required",
            "title"         =>  "required",
            "ica_pastor"    =>  "required",
        ]);

        if ($validator->fails()){
            return response()->json(["message"=>"Name, title and ICA pastor attributes required."],400);
        }

        $slug=Str::slug($request->name).date("-Y-m-d");
        $avatar=$request->avatar != null? $this->uploadImage($slug,$request->avatar) : "images/avatar.png";

        $author=new Author([
            "avatar"        =>  $avatar,
            "name"          =>  $request->name,
            "suffix"        =>  $request->suffix,
            "slug"          =>  $slug,
            "title"         =>  $request->title,
            "biography"     =>  Purifier::clean($request->biography),
            "ica_pastor"    =>  $request->ica_pastor?1:0,
        ]);

        $author->save();

        return response()->json(["author"=>new Resources\AuthorResource($author)],200);
    }

    /**
     * Display the specified resource.
     *
     * @param  string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($slug)
    {
        $author = Author::where('slug','=',$slug)->first();
        if (!is_object($author))
            return response()->json(["response"=>false],204);
        else {
            $sermons= Author::where('slug',$slug)->first()->sermons()->orderBy("published_at","desc")->paginate((new AppController())->paginate);
            return response()->json([
                "author"    =>  new Resources\AuthorResource($author),
//                "sermons"   =>  new Resources\SermonCollection($sermons)
            ], 200);
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function _getSermonsByAuthor($slug)
    {
        $author = Author::where('slug','=',$slug)->first();
        if (!is_object($author))
            return response()->json(["response"=>false],204);
        else {
//            $sermons= Author::where('slug',$slug)->first()->sermons()->orderBy("published_at","desc")->paginate(2);
//            return response()->json(new Resources\SermonCollection($sermons), 200);
            $sermons= Author::where('slug',$slug)->first()->sermons()->orderBy("published_at","desc")->get();
            return response()->json(Resources\SermonResource::collection($sermons), 200);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $slug)
    {

        $author = Author::where('slug','=',$slug)->first();

        if (!is_object($author))
            return response()->json(["response"=>false],204);
        else {
            $validator=Validator::make($request->all(),[
                "name"          =>  "required",
                "title"         =>  "required",
                "ica_pastor"    =>  "required"
            ]);

            if ($validator->fails()){
                return response()->json(["message"=>"Name, title, avatar and ICA pastor attributes required."],400);
            }

            $slug=Str::slug($request->name).date("-Y-m-d");

            $author->update([
                "name"          =>  $request->name,
                "suffix"          =>  $request->suffix,
                "slug"          =>  Str::slug($request->name).date("-Y-m-d"),
                "title"         =>  $request->title,
                "biography"     =>  Purifier::clean($request->biography),
                "ica_pastor"    =>  $request->ica_pastor,
            ]);

            if($request->avatar){
                if(file_exists($author->avatar) && $author->avatar!=="images/avatar.png"){
                    Storage::disk("public_uploads")->delete($author->avatar);
                }

                $avatar=$this->uploadImage($slug,$request->avatar);
                $author->update([
                    "avatar" => $avatar
                ]);

            }

            return response()->json(["author"=>new Resources\AuthorResource($author)],200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function trash($slug)
    {
        $author = Author::where('slug','=',$slug)->first();
        if (!is_object($author))
            return response()->json(["response"=>false],204);
        else {
            if($author->sermons->count()>0){
                foreach ($author->sermons as $sermon){
                    $sermon->forceDelete();
                }
            }
            $author->delete();
            return response()->json(["response" => true], 200);
        }
    }
    /**
     * Restores the specified resource.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($slug)
    {
        $author = Author::onlyTrashed()->where('slug','=',$slug)->first();
        if (!is_object($author))
            return response()->json(["response"=>false],204);
        else {
            $author->restore();
            return response()->json(["response" => true], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($slug)
    {
        $author = Author::onlyTrashed()->where('slug','=',$slug)->first();
        if (!is_object($author))
            return response()->json(["response"=>false],204);
        else {
            if(file_exists($author->avatar) && $author->avatar!=="images/avatar.png"){
                Storage::disk("public_uploads")->delete($author->avatar);
            }
            $author->forceDelete();
            return response()->json(["response" => true], 200);
        }
    }

    /**
     * Upload image
     *
     * @param  string $encodedFile
     * @return string
     */
    private function uploadImage($name,$encodedFile){

        //upload new picture
        $explodedFile=explode(',',$encodedFile);
        //$decodedFile=base64_decode($explodedFile[1]);

        //develop name
        $ext=$this->getExtension($explodedFile);
        $filename="images/".$name."-".uniqid().".".$ext;

        if($ext=='jpg' || $ext=='png'){
            try{
                Storage::disk('public_uploads')->put(
                    $filename,file_get_contents($encodedFile)
                );
            }catch (\RuntimeException $e){
                return response()->json([
                    'message' => "Failed to upload",
                ],501);
            }
        }else {
            return response()->json([
                'message' => "Invalid extension",
            ],415);
        }

        return $filename;
    }

    private function getExtension($explodedImage)
    {
        $imageExtensionDecode=explode('/',$explodedImage[0]);
        $imageExtension=explode(';',$imageExtensionDecode[1]);
        $lowercaseExt=strtolower($imageExtension[0]);
        if($lowercaseExt=='jpeg')
            return 'jpg';
        else
            return $lowercaseExt;
    }

}
