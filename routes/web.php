<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\UserController;
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

Route::get('/auth/{provider}/redirect', [AuthController::class,"redirect"])->name('auth.provider');

Route::get('/auth/{provider}/callback', [AuthController::class,"callback"]);

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

    Route::group(["prefix"=>"users"],function (){
        Route::get('/', [UserController::class,"index"])->name('users.index');
        Route::post('/change-role', [UserController::class,"changeAdminRole"])->name('users.change-role');
    });

    Route::group(["prefix"=>"members"],function (){
        // Route::get('/show/{code}', [RegisterController::class,"show"])->name('registers.show');
        Route::post('/register', [MemberController::class,"store"])->name(name: 'members.store');
        // Route::post('/record-attendance', [RegisterController::class,"recordAttendance"])->name('registers.record-attendance');
    });

});
