<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::post("/login",'App\Http\Controllers\Api\AdminController@login');
Route::post("/register",'App\Http\Controllers\Api\AdminController@register');
Route::get("/getSite",'App\Http\Controllers\SiteController@getSite');
Route::post("/otplogin",'App\Http\Controllers\Api\AdminController@otplogin');
Route::post("/otpValidate",'App\Http\Controllers\Api\AdminController@otpValidate');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});