<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Newe;
use App\Models\Newscategorie;
use App\Models\Post;
use App\Models\User;
use App\Notifications\Authentication;
use Dirape\Token\Token;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Twilio\Rest\Client;

class AdminController extends Controller
{
    public function register(Request $request)
    {
        $rules =array(
            "mobile" => "required",
            "email" => "required"
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else {
            $user= User::where('mobile', $request->mobile)->where('email', $request->email)->first();
            if (!$user) {
                $user = new User();
                $user->email = $request->email;
                $user->mobile = $request->mobile;
                $otp = rand(111111, 999999);
                $receiverNumber = $request->mobile;
                $message = "Your ICECREAM Register OTP Code: ".$otp." Please Do not Share With others.";
                try {
                    $account_sid = getenv("TWILIO_SID");
                    $auth_token = getenv("TWILIO_TOKEN");
                    $twilio_number = getenv("TWILIO_FROM");

                    $client = new Client($account_sid, $auth_token);
                    $client->messages->create($receiverNumber, [
                        'from' => $twilio_number,
                        'body' => $message]);
                    $user->otp = $otp;
                    $user->save();
                    $user->notify(new Authentication($user));

                    return response(["status" => "Done","message" => "OTP Sent to User"], 200);
                } catch (Exception $e) {
                    // dd("Error: ". $e->getMessage());
                    return response(["status" => "Done","message" => $e->getMessage()], 200);
                }
            } else {
                return response(["status" => "failed","message" => "Phone number is not registered or Different User type Login"], 200);
            }
        }
    }
    public function login(Request $request)
    {
        $rules =array(
            "user_cred" => "required",
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else {
            if(User::where('mobile',$request->user_cred)->first()){
                $user= User::where('mobile',$request->user_cred)->first();
            }
            elseif(User::where('email',$request->user_cred)->first()){
                $user= User::where('email',$request->user_cred)->first();
            }else{
                return response(["status" => "failed","message" => "Invalid OTP"], 200);
            }
            if ($user) {
                $otp = rand(111111, 999999);
                // $receiverNumber = $request->mobile;
                $message = "Your ICECREAM Login OTP Code: ".$otp." Please Do not Share With others.";
                try {
                    $account_sid = getenv("TWILIO_SID");
                    $auth_token = getenv("TWILIO_TOKEN");
                    $twilio_number = getenv("TWILIO_FROM");

                    $client = new Client($account_sid, $auth_token);
                    $client->messages->create($user->mobile, [
                        'from' => $twilio_number,
                        'body' => $message]);
                    $user->otp = $otp;
                    $user->save();
                    $user->notify(new Authentication($user));

                    return response(["status" => "Done","message" => "OTP Sent to User"], 200);
                } catch (Exception $e) {
                    // dd("Error: ". $e->getMessage());
                    return response(["status" => "Done","message" => $e->getMessage()], 200);
                }
            } else {
                return response(["status" => "failed","message" => "Phone number is not registered or Different User type Login"], 200);
            }
        }
    }

    function otpValidate(Request $request){
    
        $rules =array(
            "user_cred" => "",
            "otp" => "required",
        );
        $validator= Validator::make($request->all(),$rules);
        if($validator->fails()){
            return $validator->errors();
        }
        else{
            if(User::where('mobile',$request->user_cred)->where('otp',$request->otp)->first()){
                $user= User::where('mobile',$request->user_cred)->where('otp',$request->otp)->first();
            }
            elseif(User::where('email',$request->user_cred)->where('otp',$request->otp)->first()){
                $user= User::where('email',$request->user_cred)->where('otp',$request->otp)->first();
            }else{
                return response(["status" => "failed","message" => "Invalid OTP"], 200);
            }
            if($user){
                $otp = "";
                $user->otp = $otp;
                $user->user_token = (new Token())->Unique('users', 'user_token', 60);   
                $user->save();
                $token = $user->createToken('my-app-token')->plainTextToken;
                return response(["status" => "Sucess","message" => "Login Successfully","data" => $user,"token" => $token], 200);
            }
            else{
                return response(["status" => "failed","message" => "Invalid OTP"], 200);
            }
        }
    }

    public function updateUser(Request $request)
        {
        $rules =array(
            "token" => "required",
            "email" => "required|email",
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else{
            if(!User::where('user_token',$request->token)->first()){
                return response(["status" =>"failed", "message"=>"User token is not Admin Access token"], 401);
            }
            if(!User::where('email',$request->email)->where('user_token',$request->token)->first())
            {
                return response(["status" =>"failed", "message"=>"User is not Registered"], 401);
            }
            $user = User::where('email',$request->email)->first();
            }
            
            if (isset($request->name)) {
                $user->name = $request->name;
            }
            if (isset($request->mobile)) {
                $user->mobile = $request->mobile;
            }
            if (isset($request->user_type)) {
                $user->user_type = $request->user_type;
            }
            if ($request->hasFile('user_profile')) {
                $file = $request->file('user_profile')->store('public/img/user_profile');
                $user->user_profile  = $file;
            }
            $result= $user->save();
            if($result){
                $response = [
                'status' => true,
                "message" => "User changed successfully",
                "updated-User" => $user
             ];
                return response($response, 200);
            }
        }
    public function updateUserAdmin(Request $request)
        {
        $rules =array(
            "token" => "required",
            "email" => "required|email",
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else{
            if(!User::where('user_token',$request->token)->where('user_type','admin')->first()){
                return response(["status" =>"failed", "message"=>"User token is not Admin Access token"], 401);
            }
            if(!User::where('email',$request->email)->where('user_token',$request->token)->first())
            {
                return response(["status" =>"failed", "message"=>"User is not Registered"], 401);
            }
            $user = User::where('email',$request->email)->first();
            }
            
            if (isset($request->name)) {
                $user->name = $request->name;
            }
            if (isset($request->mobile)) {
                $user->mobile = $request->mobile;
            }
            if (isset($request->user_type)) {
                $user->user_type = $request->user_type;
            }
            if ($request->hasFile('user_profile')) {
                $file = $request->file('user_profile')->store('public/img/user_profile');
                $user->user_profile  = $file;
            }
            $result= $user->save();
            if($result){
                $response = [
                'status' => true,
                "message" => "User changed successfully",
                "updated-User" => $user
             ];
                return response($response, 200);
            }
        }
        public function addPost(Request $request)
        {
        $rules =array(
            "token" => "required",
            "name" => "required",
            "image" => "required",
            "description" => "required",
            "location" => "required",
            "start" => "required",
            "end" => "required",
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else{
            
            if(!User::where('user_token',$request->token)->first())
            {
                return response(["status" =>"failed", "message"=>"Invaild User Self Token"], 401);
            }
            $user = User::where('user_token',$request->token)->first();
            }
            $blog = new Post();
            $blog->name = $request->name;
            $blog->description = $request->description;
            $blog->user_id = $user->id;
            if($request->hasFile('image'))
            $file = $request->file('image')->store('public/img/blogs');
            $blog->image = $file;
            $blog->location = $request->location;
            $blog->end = $request->end;
            $blog->start = $request->start;
            $result= $blog->save();
            if($result){
                $response = [
                'status' => true,
                "message" => "Post successfully Saved",
                "updated-User" => $blog
             ];
                return response($response, 200);
            }
        }
        public function addNewsCategory(Request $request)
        {
            $rules =array(
                "category_name" => "required",
                "token" => "required",
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                if(!User::where('user_token',$request->token)->where('user_type','admin')->first()){
                    return response(["status" => "error", "message" =>"Categorie is not created | False Token"], 401);
                }
                $user = new Newscategorie();
                $user->name = $request->category_name;
                $result = $user->save();
                if ($result) {
                    $response = [
                                 'Status' => 'success',
                                 'message' => 'Categorie was successfully created',
                                 'data' => $user,
                 ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function getNewsCategory(Request $request)
        {
            $rules =array(
               
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
               
                $user = Newscategorie::all();
                if (true) {
                    $response = [
                                 'Status' => 'success',
                                 'message' => 'All News Categories',
                                 'data' => $user,
                 ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function getNews(Request $request)
        {
            $rules =array(
               
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
               
                $user = Newscategorie::all();
                if (true) {
                    $response = [
                                 'Status' => 'success',
                                 'message' => 'All News Categories',
                                 'data' => $user,
                 ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function addNews(Request $request)
        {
            $rules =array(
                "category_id" => "required",
                "token" => "required",
                "image" => "required",
                "content" => "required",
                "title" => "required",
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                if(!User::where('user_token',$request->token)->where('user_type','admin')->first()){
                    return response(["status" => "error", "message" =>"Categorie is not created | False Token"], 401);
                }
                $user = new Newe();
                $user->title = $request->title;
                $user->content = $request->content;
                $user->category_id = $request->category_id;
                if($request->hasFile('image'))
                $file = $request->file('image')->store('public/img/news');
                $user->image = $file;
                $result = $user->save();
                if ($result) {
                    $response = [
                                 'Status' => 'success',
                                 'message' => 'Categorie was successfully created',
                                 'data' => $user,
                 ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        
        
}