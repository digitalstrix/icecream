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
Route::post("/updateUser",'App\Http\Controllers\Api\AdminController@updateUser');
Route::post("/updateUserAdmin",'App\Http\Controllers\Api\AdminController@updateUserAdmin');
Route::post("/addPost",'App\Http\Controllers\Api\AdminController@addPost');
Route::post("/addNewsCategory",'App\Http\Controllers\Api\AdminController@addNewsCategory');
Route::get("/getNewsCategory",'App\Http\Controllers\Api\AdminController@getNewsCategory');
Route::post("/addNews",'App\Http\Controllers\Api\AdminController@addNews');
Route::get("/getPosts",'App\Http\Controllers\Api\AdminController@getPosts');
Route::get("/getNews",'App\Http\Controllers\Api\AdminController@getNews');
Route::Post("/addLike",'App\Http\Controllers\Api\AdminController@addLike');
Route::get("/getLikeCount",'App\Http\Controllers\Api\AdminController@getLikeCount');
Route::post("/addFollwer",'App\Http\Controllers\Api\AdminController@addFollwer');
Route::get("/imFollowing",'App\Http\Controllers\Api\AdminController@imFollowing');
Route::post("/addProductCategory",'App\Http\Controllers\Api\AdminController@addProductCategory');
Route::post("/addProductSubCategory",'App\Http\Controllers\Api\AdminController@addProductSubCategory');
Route::post("/addProduct",'App\Http\Controllers\Api\AdminController@addProduct');
Route::get("/getProduct",'App\Http\Controllers\Api\AdminController@getProduct');
Route::get("/getProductById",'App\Http\Controllers\Api\AdminController@getProductById');
Route::post("/addChat",'App\Http\Controllers\Api\AdminController@addChat');
Route::get("/getConversion",'App\Http\Controllers\Api\AdminController@getConversion');
Route::post("/createGroup",'App\Http\Controllers\Api\AdminController@createGroup');
Route::get("/getGroup",'App\Http\Controllers\Api\AdminController@getGroup');
Route::post("/addMemberInGroup",'App\Http\Controllers\Api\AdminController@addMemberInGroup');
Route::delete("/removeMemberFromGroup",'App\Http\Controllers\Api\AdminController@removeMemberFromGroup');
Route::post("/sendMessageInGroup",'App\Http\Controllers\Api\AdminController@sendMessageInGroup');
Route::get("/getGroupById",'App\Http\Controllers\Api\AdminController@getGroupById');
Route::post("/registerAdmin",'App\Http\Controllers\Api\AdminController@registerAdmin');
Route::post("/loginAdmin",'App\Http\Controllers\Api\AdminController@loginAdmin');
Route::post("/addBlog",'App\Http\Controllers\Api\AdminController@addBlog');
Route::get("/getBlog",'App\Http\Controllers\Api\AdminController@getBlog');


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});