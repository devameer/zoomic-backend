<?php

header('X-Frame-Options: DENY');
//header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: *');


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

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

Route::group(['middleware' => ['cors']], function () {

    Route::group(['middleware' => ['guest:api']], function () {
        Route::post('register', [\App\Http\Controllers\UserController::class, 'register']);
        Route::post('login', [\App\Http\Controllers\UserController::class, 'login']);
        Route::post('password/forgot', [\App\Http\Controllers\UserController::class, 'forgot_password']);
        Route::post('password/reset', [\App\Http\Controllers\UserController::class, 'reset_password']);
    });

    Route::group(['middleware' => ['auth:api']], function () {
        Route::get('user', [\App\Http\Controllers\UserController::class, 'current_user']);
        Route::put('user', [\App\Http\Controllers\UserController::class, 'update_profile']);
        Route::post('logout', [\App\Http\Controllers\UserController::class, 'logout']);
        Route::post('password/change', [\App\Http\Controllers\UserController::class, 'change_password']);

        Route::post('posts/{post}/comment', [\App\Http\Controllers\PostController::class, 'comment']);
        Route::put('posts/{post}/comment/{comment}', [\App\Http\Controllers\PostController::class, 'updateComment']);
        Route::delete('posts/{post}/comment/{comment}', [\App\Http\Controllers\PostController::class, 'deleteComment']);

    });

    Route::group(['middleware' => ['is_admin']], function () {
        Route::post('/upload', [App\Http\Controllers\HomeController::class, 'upload'])->name('upload');
        Route::get('/upload', [App\Http\Controllers\HomeController::class, 'uploads']);
        Route::get('latest_comments', [\App\Http\Controllers\PostController::class, 'latestComments']);
        Route::get('requested_services', [\App\Http\Controllers\HomeController::class, 'requestedServices']);
        Route::get('requested_services/{id}', [\App\Http\Controllers\HomeController::class, 'requestedService']);
        Route::apiResource('users', \App\Http\Controllers\UserController::class);
    });

    Route::get('pages/{page}/check_slug', [\App\Http\Controllers\PageController::class, 'checkSlug']);
    Route::get('categories/check_slug', [\App\Http\Controllers\CategoryController::class, 'checkSlug']);
    Route::get('posts/check_slug', [\App\Http\Controllers\PostController::class, 'checkSlug']);

    Route::post('contact_us', [\App\Http\Controllers\HomeController::class, 'contactUs']);
    Route::post('request_services', [\App\Http\Controllers\HomeController::class, 'requestService']);

    Route::apiResource('categories', \App\Http\Controllers\CategoryController::class);
    Route::apiResource('pages', \App\Http\Controllers\PageController::class);
    Route::post('pages/{page}/items/{item}/rate', [\App\Http\Controllers\PageItemController::class, 'rate']);
    Route::apiResource('pages/{page}/items', \App\Http\Controllers\PageItemController::class);
    Route::apiResource('posts', \App\Http\Controllers\PostController::class);


});
