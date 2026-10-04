<?php

//use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::group(["prefix"=>"1.0.0"],function (){

    Route::post('/seeder',[API\V1_0_0\AppController::class, 'seeder']);

    /* Home Page */
    Route::get('/dashboard',[API\V1_0_0\AppController::class, 'dashboard']);

    /* Sermons */
    Route::group(["prefix"=>"sermons"],function () {
        Route::get('/search/{query}', [API\V1_0_0\SermonController::class, 'search']);
        Route::get('/', [API\V1_0_0\SermonController::class, 'index']);
        Route::get('/series/{slug}', [API\V1_0_0\SermonController::class, 'bySeries']);
        Route::get('/authors/{slug}', [API\V1_0_0\SermonController::class, 'getSermonsByAuthor']);
        Route::post('/',[API\V1_0_0\SermonController::class,'store']);
    });

    /* Series */
    Route::group(["prefix"=>"series"],function () {
        Route::get('/search/{query}', [API\V1_0_0\SeriesController::class, 'search']);
        Route::get('/', [API\V1_0_0\SeriesController::class, 'index']);
    });

    /* Authors */
    Route::group(["prefix"=>"authors"],function () {
        Route::get('/', [API\V1_0_0\AuthorController::class, 'index']);
    });

    /* Prayers */
    Route::group(["prefix"=>"prayers"],function (){
        Route::get('/',[API\V1_0_0\PrayerController::class, 'index']);
    });
});

Route::group(["prefix"=>"1.1"],function (){

    Route::post('/seeder',[API\V1_1\AppController::class, 'seeder']);

    /* Home Page */
    Route::get('/initiate',[API\V1_1\AppController::class, 'initiate']);
    Route::get('/dashboard/{timestamp}',[API\V1_1\AppController::class, 'dashboard']);
    Route::get('/search/{query}', [API\V1_1\AppController::class, 'search']);

    /* Sermons */
    Route::group(["prefix"=>"sermons"],function () {
        Route::get('/', [API\V1_1\SermonController::class, 'index']);
        Route::get('/get/{timestamp}', [API\V1_1\SermonController::class, 'getSermons']);
        Route::get('/series/{slug}', [API\V1_1\SermonController::class, 'bySeries']);
        Route::get('/authors/{slug}', [API\V1_1\SermonController::class, 'getSermonsByAuthor']);
    });


    /* Prayers */
    Route::group(["prefix"=>"prayers"],function (){
        Route::get('/',[API\V1_1\PrayerController::class, 'index']);
    });

    /* Downloads */
    Route::group(["prefix"=>"downloads"],function (){
        Route::get('/',[API\V1_1\DownloadController::class, 'index']);
    });

    Route::get('/twitter',[API\V1_1\TwitterController::class, 'index']);
});

Route::group(["prefix"=>"1.2"],function (){

    /* Home Page */
    Route::get('/initiate',[API\V1_1\AppController::class, 'initiate']);
    Route::get('/dashboard/{timestamp}',[API\V1_2\AppController::class, 'dashboard']);
    Route::get('/search/{query}', [API\V1_1\AppController::class, 'search']);

    /* Sermons */
    Route::group(["prefix"=>"sermons"],function () {
        Route::get('/', [API\V1_2\SermonController::class, 'index']);
        Route::get('/get/{timestamp}', [API\V1_2\SermonController::class, 'getSermons']);
        Route::get('/view/{slug}', [API\V1_2\SermonController::class, 'show']);
        Route::get('/series/{slug}', [API\V1_2\SermonController::class, 'bySeries']);
        Route::get('/authors/{slug}', [API\V1_2\SermonController::class, 'getSermonsByAuthor']);
    });

    Route::post('/users/login', [API\V1_2\UserController::class, 'login']);

    Route::group(["prefix"=>"cells", "middleware"=>"auth:sanctum"], function (){
        Route::get('/{code}/get', [API\V1_2\CellController::class, 'show']);
        Route::get('/unverified', [API\V1_2\CellController::class, 'unverified']);
        Route::post('/', [\App\Http\Controllers\Web\CellController::class, 'store']);
        Route::post('/verify', [\App\Http\Controllers\Web\CellController::class, 'verify']);
        Route::post('/meetings', [API\V1_2\MeetingController::class, 'store']); // legacy pointed at Web store, which needs a {code} this route lacks
        
    });

    Route::group(["prefix"=>"meetings", "middleware"=>"auth:sanctum"], function (){
        Route::post('/', [\App\Http\Controllers\Web\MeetingController::class, 'store']);
        Route::post('/{code}', [API\V1_2\MeetingController::class, 'update']);
    });

    Route::group(["prefix"=>"meetings", "middleware"=>"auth:sanctum"], function (){
        Route::post('/', [API\V1_2\MeetingController::class, 'store']);
        Route::post('/{code}', [API\V1_2\MeetingController::class, 'update']);
    });

    Route::group(["prefix"=>"members", "middleware"=>"auth:sanctum"], function (){
        Route::post('/', [API\V1_2\MemberController::class, 'store']);
    });

    Route::group(["prefix"=>"transactions", "middleware"=>"auth:sanctum"], function (){
        Route::post('/', [API\V1_2\TransactionController::class, 'store']);
    });

    Route::group(["prefix"=>"highlights", "middleware"=>"auth:sanctum"], function (){
        Route::post('/', [API\V1_2\HighlightController::class, 'store']);
    });

    Route::group(["prefix"=>"data", "middleware"=>"auth:sanctum"], function (){
//        Route::get('/', [API\V1_2\AppController::class, 'authData']);
        Route::post('/', [API\V1_2\AppController::class, 'syncData']);
    });

    /* Downloads */
    Route::group(["prefix"=>"downloads"],function (){
        Route::get('/',[API\V1_1\DownloadController::class, 'index']);
    });

//    Route::post('/notification',[\App\Http\Controllers\Web\NotificationController::class, 'pushNotification']);


});

Route::group(["prefix"=>"1.3"],function (){

    /* Home Page */
    Route::get('/initiate',[API\V1_1\AppController::class, 'initiate']);
    Route::get('/dashboard/{timestamp}',[API\V1_3\AppController::class, 'dashboard']);
    Route::get('/search/{query}', [API\V1_1\AppController::class, 'search']);

    /* Sermons */
    Route::group(["prefix"=>"sermons"],function () {
        Route::get('/', [API\V1_2\SermonController::class, 'index']);
        Route::get('/get/{timestamp}', [API\V1_2\SermonController::class, 'getSermons']);
        Route::get('/view/{slug}', [API\V1_2\SermonController::class, 'show']);
        Route::get('/series/{slug}', [API\V1_2\SermonController::class, 'bySeries']);
        Route::get('/authors/{slug}', [API\V1_2\SermonController::class, 'getSermonsByAuthor']);
    });

    Route::post('/users/login', [API\V1_3\UserController::class, 'login']);

    Route::group(["prefix"=>"cells", "middleware"=>"auth:sanctum"], function (){
        Route::get('/{code}/get', [API\V1_2\CellController::class, 'show']);
        Route::get('/unverified', [API\V1_2\CellController::class, 'unverified']);
        Route::post('/', [\App\Http\Controllers\Web\CellController::class, 'store']);
        Route::post('/verify', [\App\Http\Controllers\Web\CellController::class, 'verify']);
        Route::post('/meetings', [API\V1_2\MeetingController::class, 'store']); // legacy pointed at Web store, which needs a {code} this route lacks
    });

    Route::group(["prefix"=>"meetings", "middleware"=>"auth:sanctum"], function (){
        Route::post('/', [\App\Http\Controllers\Web\MeetingController::class, 'store']);
        Route::post('/{code}', [API\V1_2\MeetingController::class, 'update']);
    });

    Route::group(["prefix"=>"meetings", "middleware"=>"auth:sanctum"], function (){
        Route::post('/', [API\V1_2\MeetingController::class, 'store']);
        Route::post('/{code}', [API\V1_2\MeetingController::class, 'update']);
    });

    Route::group(["prefix"=>"members", "middleware"=>"auth:sanctum"], function (){
        Route::post('/', [API\V1_3\MemberController::class, 'store']);
    });

    Route::group(["prefix"=>"transactions", "middleware"=>"auth:sanctum"], function (){
        Route::post('/', [API\V1_2\TransactionController::class, 'store']);
    });

    Route::group(["prefix"=>"highlights", "middleware"=>"auth:sanctum"], function (){
        Route::post('/', [API\V1_2\HighlightController::class, 'store']);
    });

    Route::group(["prefix"=>"data", "middleware"=>"auth:sanctum"], function (){
        Route::post('/', [API\V1_3\AppController::class, 'syncData']);
        Route::post('/delete', [API\V1_3\AppController::class, 'deleteData']);
    });

    /* Downloads */
    Route::group(["prefix"=>"downloads"],function (){
        Route::get('/',[API\V1_1\DownloadController::class, 'index']);
    });


    Route::group(["prefix"=>"registers", "middleware"=>"auth:sanctum"], function (){
        Route::get('/', [API\V1_3\RegisterController::class, 'index']);
        Route::post('/', [API\V1_3\RegisterController::class, 'store']);
        Route::get('/{code}/attendance', [API\V1_3\RegisterController::class, 'attendance']);
        Route::post('/attendance', [API\V1_3\RegisterController::class, 'recordAttendance']);
    });

//    Route::post('/notification',[\App\Http\Controllers\Web\NotificationController::class, 'pushNotification']);


});

Route::group(["prefix"=>"1.3.5"],function (){

    /* Home Page */
    Route::get('/initiate',[API\V1_1\AppController::class, 'initiate']);
    Route::get('/dashboard/{timestamp}',[API\V1_3\AppController::class, 'dashboard']);
    Route::get('/search/{query}', [API\V1_1\AppController::class, 'search']);

    /* Sermons */
    Route::group(["prefix"=>"sermons"],function () {
        Route::get('/', [API\V1_2\SermonController::class, 'index']);
        Route::get('/get/{timestamp}', [API\V1_2\SermonController::class, 'getSermons']);
        Route::get('/view/{slug}', [API\V1_2\SermonController::class, 'show']);
        Route::get('/register-view/{slug}', [API\V1_3\SermonController::class, 'registerView']);
        Route::get('/series/{slug}', [API\V1_2\SermonController::class, 'bySeries']);
        Route::get('/authors/{slug}', [API\V1_2\SermonController::class, 'getSermonsByAuthor']);
    });

    Route::post('/users/login', [API\V1_3\UserController::class, 'login']);
    Route::post('/users/confirm', [API\V1_3\UserController::class, 'confirm']);

    Route::group(["prefix"=>"cells", "middleware"=>"auth:sanctum"], function (){
        Route::get('/{code}/get', [API\V1_2\CellController::class, 'show']);
        Route::get('/unverified', [API\V1_2\CellController::class, 'unverified']);
        Route::post('/', [\App\Http\Controllers\Web\CellController::class, 'store']);
        Route::post('/verify', [\App\Http\Controllers\Web\CellController::class, 'verify']);
        Route::post('/meetings', [API\V1_2\MeetingController::class, 'store']); // legacy pointed at Web store, which needs a {code} this route lacks
        Route::post('/attach-members/{code}', [API\V1_2\CellController::class, 'attachMembers']);
        Route::post('/update/{code}', [API\V1_2\CellController::class, 'update']);
    });

    Route::group(["prefix"=>"meetings", "middleware"=>"auth:sanctum"], function (){
        Route::post('/', [\App\Http\Controllers\Web\MeetingController::class, 'store']);
        Route::post('/{code}', [API\V1_2\MeetingController::class, 'update']);
    });

    Route::group(["prefix"=>"meetings", "middleware"=>"auth:sanctum"], function (){
        Route::post('/', [API\V1_2\MeetingController::class, 'store']);
        Route::post('/{code}', [API\V1_2\MeetingController::class, 'update']);
    });

    Route::group(["prefix"=>"ministries", "middleware"=>"auth:sanctum"], function (){
        Route::get('/', [API\V1_3\MinistryController::class, 'index']);
        Route::get('/show/{id}', [API\V1_3\MinistryController::class, 'show']);
    });

    Route::group(["prefix"=>"members", "middleware"=>"auth:sanctum"], function (){
        Route::get('/', [API\V1_3\MemberController::class, 'index']);
        Route::post('/', [API\V1_3\MemberController::class, 'store']);
        Route::post('/batch-add', [API\V1_3\MemberController::class, 'batchAdd']);
         Route::post('/update/{code}', [API\V1_3\MemberController::class, 'update']);
    });

    Route::group(["prefix"=>"transactions", "middleware"=>"auth:sanctum"], function (){
        Route::post('/', [API\V1_2\TransactionController::class, 'store']);
    });

    Route::group(["prefix"=>"highlights", "middleware"=>"auth:sanctum"], function (){
        Route::post('/', [API\V1_2\HighlightController::class, 'store']);
    });

    Route::group(["prefix"=>"data", "middleware"=>"auth:sanctum"], function (){
        Route::post('/', [API\V1_3\AppController::class, 'syncData']);
        Route::post('/delete', [API\V1_3\AppController::class, 'deleteData']);
    });

    /* Downloads */
    Route::group(["prefix"=>"downloads"],function (){
        Route::get('/',[API\V1_1\DownloadController::class, 'index']);
    });


    Route::group(["prefix"=>"registers", "middleware"=>"auth:sanctum"], function (){
        Route::get('/', [API\V1_3\RegisterController::class, 'index']);
        Route::post('/', [API\V1_3\RegisterController::class, 'store']);
        Route::get('/{code}/attendance', [API\V1_3\RegisterController::class, 'attendance']);
        Route::post('/attendance', [API\V1_3\RegisterController::class, 'recordAttendance']);
        Route::post('/attendance/self-registration/{code}', [API\V1_3\RegisterController::class, 'selfRegistration']);
    });

//    Route::post('/notification',[\App\Http\Controllers\Web\NotificationController::class, 'pushNotification']);


});

/*
|--------------------------------------------------------------------------
| API v2 — clean rebuild
|--------------------------------------------------------------------------
|
| Supersedes 1.0.0 through 1.3.5 for any mobile client updated to target
| it. The legacy version groups above are left completely untouched and
| live — the current Flutter app keeps working against them until it's
| separately migrated to v2, at which point the whole legacy tree can be
| retired in one pass.
|
*/

Route::group(["prefix" => "2.0"], function () {

    /* Content sync — each type genuinely paginated (page/perPage query
       params), replacing the old /dashboard/{timestamp} endpoint's
       limit(20)-per-type cap, which silently dropped data beyond the
       first 20 changed records with no way to page further. */
    Route::get('/sermons', [API\V2\AppController::class, 'sermons']);
    Route::get('/series', [API\V2\AppController::class, 'series']);
    Route::get('/authors', [API\V2\AppController::class, 'authors']);
    Route::get('/prayers', [API\V2\AppController::class, 'prayers']);
    Route::get('/events', [API\V2\AppController::class, 'events']);
    Route::get('/downloads', [API\V2\DownloadController::class, 'index']);
    Route::get('/search', [API\V2\SearchController::class, 'index']);

    /* Auth */
    Route::post('/auth/login', [API\V2\AuthController::class, 'login']);
    Route::post('/auth/confirm', [API\V2\AuthController::class, 'confirm']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [API\V2\AppController::class, 'me']);

        /* Highlights/bookmarks/notes — one canonical, user-scoped surface
           (index to fetch, store/destroy per type) replacing the legacy
           syncData/deleteData pair and the separately-broken
           V1_2\BookmarkController::store. */
        Route::get('/annotations', [API\V2\AnnotationController::class, 'index']);
        Route::post('/annotations/highlights', [API\V2\AnnotationController::class, 'storeHighlight']);
        Route::delete('/annotations/highlights/{highlight}', [API\V2\AnnotationController::class, 'destroyHighlight']);
        Route::post('/annotations/bookmarks', [API\V2\AnnotationController::class, 'storeBookmark']);
        Route::delete('/annotations/bookmarks/{bookmark}', [API\V2\AnnotationController::class, 'destroyBookmark']);
        Route::post('/annotations/notes', [API\V2\AnnotationController::class, 'storeNote']);
        Route::delete('/annotations/notes/{note}', [API\V2\AnnotationController::class, 'destroyNote']);

        /* Ministries, members, registers/attendance */
        Route::get('/ministries', [API\V2\MinistryController::class, 'index']);
        Route::get('/ministries/{id}', [API\V2\MinistryController::class, 'show']);

        Route::get('/members', [API\V2\MemberController::class, 'index']);
        Route::post('/members', [API\V2\MemberController::class, 'store']);
        Route::post('/members/batch-add', [API\V2\MemberController::class, 'batchAdd']);
        Route::post('/members/{code}', [API\V2\MemberController::class, 'update']);

        Route::get('/registers', [API\V2\RegisterController::class, 'index']);
        Route::post('/registers', [API\V2\RegisterController::class, 'store']);
        Route::get('/registers/{code}/attendance', [API\V2\RegisterController::class, 'attendance']);
        Route::post('/registers/attendance', [API\V2\RegisterController::class, 'recordAttendance']);
        Route::post('/registers/{code}/self-registration', [API\V2\RegisterController::class, 'selfRegistration']);

        /* Cells, meetings, transactions — small-groups + giving */
        Route::get('/cells/unverified', [API\V2\CellController::class, 'unverified']);
        Route::get('/cells/{code}', [API\V2\CellController::class, 'show']);
        Route::post('/cells', [API\V2\CellController::class, 'store']);
        Route::post('/cells/verify', [API\V2\CellController::class, 'verify']);
        Route::post('/cells/{code}', [API\V2\CellController::class, 'update']);
        Route::post('/cells/{code}/attach-members', [API\V2\CellController::class, 'attachMembers']);

        Route::post('/meetings', [API\V2\MeetingController::class, 'store']);
        Route::post('/meetings/{code}', [API\V2\MeetingController::class, 'update']);

        Route::post('/transactions', [API\V2\TransactionController::class, 'store']);
    });
});

