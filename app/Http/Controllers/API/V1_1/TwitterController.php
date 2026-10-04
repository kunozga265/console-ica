<?php

namespace App\Http\Controllers\API\V1_1;

use App\Http\Controllers\Controller;

class TwitterController extends Controller
{
    public function index()
    {
        // The atymic/twitter package (and Twitter's free API) is no longer available;
        // return an empty feed so older app builds that still call this don't error.
        if (! class_exists(\Atymic\Twitter\Facade\Twitter::class)) {
            return response()->json(["data" => []]);
        }

        return \Atymic\Twitter\Facade\Twitter::userTweets("1882487780", []);
    }
}
