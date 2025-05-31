<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\RegisterController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

//Route::get('/', function () {
//    return Inertia::render('Welcome', [
//        'canLogin' => Route::has('login'),
//        'canRegister' => Route::has('register'),
//        'laravelVersion' => Application::VERSION,
//        'phpVersion' => PHP_VERSION,
//    ]);
//});

Route::get('/auth/redirect', function () {
    return Socialite::driver('google')->redirect();
})->name('auth.google');

Route::get('/callback', function () {
    $user = Socialite::driver('google')->user();
    dd($user);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::get('/', [AppController::class,"dashboard"])->name('dashboard');


    Route::group(["prefix"=>"registers"],function (){
        Route::get('/show/{code}', [RegisterController::class,"show"])->name('registers.show');
        Route::post('/', [RegisterController::class,"store"])->name('registers.store');
        Route::post('/record-attendance', [RegisterController::class,"recordAttendance"])->name('registers.record-attendance');
    });

});
