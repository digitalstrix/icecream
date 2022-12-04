<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Assigncategorie;
use App\Models\Assignsubcategorie;
use App\Models\Blog;
use App\Models\BusinessCategory;
use App\Models\BusinessCategoryAssign;
use App\Models\BusinessSubCategory;
use App\Models\BusinessSubCategoryAssign;
use App\Models\Categorie;
use App\Models\Chat;
use App\Models\FactoryAddress;
use App\Models\Follower;
use App\Models\Group;
use App\Models\Like;
use App\Models\Managers;
use App\Models\Member;
use App\Models\Message;
use App\Models\Newe;
use App\Models\Newscategorie;
use App\Models\Packages;
use App\Models\Post;
use App\Models\Product;
use App\Models\Subcategorie;
use App\Models\Uom;
use App\Models\UomAssign;
use App\Models\User;
use App\Notifications\Authentication;
use Dirape\Token\Token;
use Exception;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
                $user->company_id = (new Token())->Unique('users', 'company_id', 5);
                $user->mobile = substr($request->mobile,-10);
                $otp = rand(111111, 999999);
                $receiverNumber = substr($request->mobile,-10);
                $message = "Your ICECREAM Register OTP Code: ".$otp." Please Do not Share With others.";
                try {
                    // $account_sid = getenv("TWILIO_SID");
                    // $auth_token = getenv("TWILIO_TOKEN");
                    // $twilio_number = getenv("TWILIO_FROM");
                    $client = new GuzzleHttpClient();
                    $request = new Psr7Request('GET', 'https://2factor.in/API/V1/f49b57a2-047f-11ea-9fa5-0200cd936042
                    /SMS/+91'.$receiverNumber.'/'.$otp.'/OTP1');
                    $res = $client->sendAsync($request)->wait();
                    echo $res->getBody();

                    // $client = new Client($account_sid, $auth_token);
                    // $client->messages->create($receiverNumber, [
                    //     'from' => $twilio_number,
                    //     'body' => $message]);
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
            $mobile = substr($request->user_cred, -10);
            if(User::where('mobile',$mobile)->first()){
                $user= User::where('mobile',$mobile)->first();
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
                    $receiverNumber = substr($user->mobile,-10);
                    // $account_sid = getenv("TWILIO_SID");
                    // $auth_token = getenv("TWILIO_TOKEN");
                    // $twilio_number = getenv("TWILIO_FROM");
                    $client = new GuzzleHttpClient();
                    $request = new Psr7Request('GET', 'https://2factor.in/API/V1/f49b57a2-047f-11ea-9fa5-0200cd936042
                    /SMS/+91'.$receiverNumber.'/'.$otp.'/OTP1');
                    $res = $client->sendAsync($request)->wait();
                    echo $res->getBody();

                    // $client = new Client($account_sid, $auth_token);
                    // $client->messages->create($user->mobile, [
                    //     'from' => $twilio_number,
                    //     'body' => $message]);
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
    public function loginAdmin(Request $request)
    {
        $rules =array(
            "email" => "required|email",
            "password" => "required|min:6",
            "user_type" => "required|in:admin",
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else {

            if($request->user_type=='admin'){
                if(!User::where('email',$request->email)->where('user_type','admin')->first()){
                    return response(["status" =>"failed", "message"=>"User is not Registered or Invaild User Type"], 401);
                }
                $user = User::where('email',$request->email)->first();
                if(!Hash::check($request->password, $user->password)){
                    return response(["status" =>"failed", "message"=>"Incorrect Password"], 401);
                }
            }
            }
            
            if ($user){
                $response = [
                'user' => $user,
                "message"=>"User is Logged IN"
            ];
                return response($response, 200);
            }
        }
        public function registerAdmin(Request $request)
        {
            $rules =array( 
                "name" => "required",
                "email" => "required",
                "password" => "required|min:6"           
                );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                if(User::where('email',$request->email)->first()){
                    return response(["status" =>"failed", "message"=>"Already Registered"], 401);
                }
                $user = new User();
                $user->name =$request->name;
                $user->email =$request->email;
                $user->mobile = substr($request->mobile,-10);
                $user->user_type ='admin';
                $user->password =Hash::make($request->password);
                $token_qr = (new Token())->Unique('users', 'user_token', 60);
                $user->user_token = $token_qr;
                $user->company_id = (new Token())->Unique('users', 'company_id', 5);
                $result= $user->save();
                if ($result) {
                    $response = [
                    'message' => 'User Created Successfully',
                    'user' => $user
                ];
                    return response($response, 200);
                }
                else
                {
                    return response(["status" =>"failed", "message"=>"User is not created"], 401);
                }
            }
        }
    function otpValidate(Request $request){
        $rules =array(
            "user_cred" => "",
            "otp" => "required"
        );
        $validator= Validator::make($request->all(),$rules);
        if($validator->fails()){
            return $validator->errors();
        }
        else{
            $mobile = substr($request->user_cred, -10);
            if(User::where('mobile',$mobile)->where('otp',$request->otp)->first()){
                $user= User::where('mobile',$mobile)->where('otp',$request->otp)->first();
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
                $category = BusinessCategoryAssign::where('user_id', $user->id)->get('business_category_id');
                $subcategory = BusinessSubCategoryAssign::where('user_id', $user->id)->get('business_sub_category_id');
                $follower = Follower::where('follow',$user->id)->get()->count();
                $following = Follower::where('followed',$user->id)->get()->count();
                $token = $user->createToken('my-app-token')->plainTextToken;
                return response(["status" => "Sucess","message" => "Login Successfully","data" => $user,"follower"=>$follower,"following"=>$following,
                "business_category"=>$category,
                "business_sub_category"=>$subcategory,
                "token" => $token], 200);
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
            if (isset($request->organization_name)) {
                $user->organization_name = $request->organization_name;
            }
            if (isset($request->short_name)) {
                $user->short_name = $request->short_name;
            }
            if (isset($request->mobile_2)) {
                $user->mobile_2 = $request->mobile_2;
            }
            if (isset($request->address_1)) {
                $user->address_1 = $request->address_1;
            }
            if (isset($request->address_2)) {
                $user->address_2 = $request->address_2;
            }
            if (isset($request->address_3)) {
                $user->address_3 = $request->address_3;
            }
            if (isset($request->country)) {
                $user->country = $request->country;
            }
            if (isset($request->state)) {
                $user->state = $request->state;
            }
            if (isset($request->city)) {
                $user->city = $request->city; //
            }
            if (isset($request->landmark)) {
                $user->landmark = $request->landmark;
            }
            if (isset($request->latitude)) {
                $user->latitude = $request->latitude;
            }
            if (isset($request->longitude)) {
                $user->longitude = $request->longitude;
            }
            if (isset($request->pan_number)) {
                $user->pan_number = $request->pan_number;
            }
            if (isset($request->gst_number)) {
                $user->gst_number = $request->gst_number;
            }
            if (isset($request->est_year)) {
                $user->est_year = $request->est_year;
            }
            if (isset($request->employee_number)) {
                $user->employee_number = $request->employee_number;
            }
            if (isset($request->turnover)) {
                $user->turnover = $request->turnover;
            }
            if (isset($request->certificate_issue)) {
                $user->certificate_issue = $request->certificate_issue;
            }
            if (isset($request->business_category)) {
                foreach($request->business_category as $category){
                if (!BusinessCategoryAssign::where('user_id', $user->id)->where('business_category_id', $category)->first()) {
                    $assign = new BusinessCategoryAssign();
                    $assign->user_id = $user->id;
                    $assign->business_category_id = $category;
                    $assign->save();
                }
                }
            }
            if (isset($request->business_sub_category)) {
                foreach($request->business_sub_category as $subcategory){
                if (!BusinessSubCategoryAssign::where('user_id', $user->id)->where('business_sub_category_id', $subcategory)->first()) {
                    $assign = new BusinessSubCategoryAssign();
                    $assign->user_id = $user->id;
                    $assign->business_sub_category_id = $subcategory;
                    $assign->save();
                }
                }
            }
            if(isset($request->password)) {
                $user->password =Hash::make($request->password);
            }

            if ($request->hasFile('user_profile')) {
                $file = $request->file('user_profile')->store('public/img/user_profile');
                $user->user_profile  = $file;
            }
            if ($request->hasFile('company_logo')) {
                $file = $request->file('company_logo')->store('public/img/company_logo');
                $user->company_logo  = $file;
            }
            if ($request->hasFile('comapany_profile')) {
                $file = $request->file('comapany_profile')->store('public/img/comapany_profile');
                $user->comapany_profile  = $file;
            }
            if ($request->hasFile('gst_image')) {
                $file = $request->file('gst_image')->store('public/img/gst_image');
                $user->gst_image  = $file;
            }
            if ($request->hasFile('pan_image')) {
                $file = $request->file('pan_image')->store('public/img/pan_image');
                $user->pan_image  = $file;
            }
            if ($request->hasFile('company_brochure')) {
                $file = $request->file('company_brochure')->store('public/img/company_brochure');
                $user->company_brochure  = $file;
            }
            if ($request->hasFile('comapny_ad')) {
                $file = $request->file('comapny_ad')->store('public/img/comapny_ad');
                $user->comapny_ad  = $file;
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
        public function addFollwer(Request $request)
        {
            $rules =array(
               "follow_id" => "required",
               "follower_id" => "required",
             );
            $validator= Validator::make($request->all(), $rules);
            if($validator->fails()) {
                return $validator->errors();
            } else {
                if(Follower::where('follow',$request->follow_id)->where('followed',$request->follower_id)->first()){
                    $follow = Follower::where('follow',$request->follow_id)->where('followed',$request->follower_id)->first()->delete();
                    return response(["status" => true, "message" =>"Unfollowed Sucessfully"], 200);
                }
                $user = new Follower();
                $user->follow = $request->follow_id;
                $user->followed = $request->follower_id;
                $user->save();
                if (true) {
                    $response = [
                                 'Status' => 'success',
                                 'message' => 'Followed successfully',
                                 'data' => $user,
                 ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function imFollowing(Request $request)
        {
            $rules =array(
                "my_id" =>"required"
             );
            $validator= Validator::make($request->all(), $rules);
            if($validator->fails()) {
                return $validator->errors();
            } else {
                $user = Follower::where('followed',$request->my_id)->get();
                if (true) {
                    $response = [
                                 'Status' => 'success',
                                 'message' => 'Im following to these users',
                                 'data' => $user,
                 ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
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
            if(!User::where('email',$request->email)->first())
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
        public function getPosts(Request $request)
        {
            $rules =array(
               
             );
            $validator= Validator::make($request->all(), $rules);
            if($validator->fails()) {
                return $validator->errors();
            } else {
                $user = Post::all();
                if (true) {
                    $response = [
                                 'Status' => 'success',
                                 'message' => 'All Posts Fetched',
                                 'data' => $user,
                 ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function addLike(Request $request)
        {
            $rules =array(
               "post_id" => "required",
               "user_id" => "required",
             );
            $validator= Validator::make($request->all(), $rules);
            if($validator->fails()) {
                return $validator->errors();
            } else {
                if(Like::where('post_id',$request->post_id)->where('user_id',$request->user_id)->first()){
                    $user = Like::where('post_id',$request->post_id)->where('user_id',$request->user_id)->first()->delete();
                    return response(["status" => true, "message" =>"Unliked Successfully"], 200);
                }else{
                    $user = new Like();
                    $user->user_id = $request->user_id;
                    $user->post_id = $request->post_id;
                    $user->save();
                }
                if (true) {
                    $response = [
                                 'Status' => 'success',
                                 'message' => 'Liked the post successfully',
                                 'data' => $user,
                 ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function getLikeCount(Request $request)
        {
            $rules =array(
               "post_id" => "required"
             );
            $validator= Validator::make($request->all(), $rules);
            if($validator->fails()) {
                return $validator->errors();
            } else {
                $post = Like::where('post_id',$request->post_id)->get();
                if (true) {
                    $response = [
                                 'like_count' => count($post),
                                 'Status' => 'success',
                                 'message' => 'All News Categories',
                                 'data' => $post,
                 ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
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
            if($validator->fails()) {
                return $validator->errors();
            } else {
                $user = Newe::all();
                if (true) {
                    $response = [
                                 'Status' => 'success',
                                 'message' => 'All News',
                                 'data' => $user,
                 ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }

        public function addProductCategory(Request $request)
        {
            $rules =array(
                "name" => "required",
                "description" => "required",
                "image" => "required"
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = new Categorie();
                $user->name = $request->name;
                $user->description = $request->description;
                if($request->hasFile('image'))
                $file = $request->file('image')->store('public/img/category');
                $user->image = $file;
                $result = $user->save();
                if ($result) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Categorie was successfully   created',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function addProductSubCategory(Request $request)
        {
            $rules =array(
                "name" => "required",
                "price" => "required",
                "quantity" => "required"
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = new Subcategorie();
                $user->name = $request->name;
                $user->price = $request->price;
                $user->quantity = $request->quantity;
                $result = $user->save();
                if ($result) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Sub Categorie was successfully   created',
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
        public function addProduct(Request $request)
        {
            $rules =array(
                "name" => "required",
                "description" => "required",
                "type" => "required|in:1,2",
                "product_code" => "required",
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = new Product();
                $user->name = $request->name;
                $user->description = $request->description;
                $user->type = $request->type;
                $user->product_code = $request->product_code;
                $user->barcode = $request->barcode;
                $user->purchase_price = $request->purchase_price;
                $user->tax_on_purchase_price = $request->tax_on_purchase_price;
                $user->sale_price = $request->sale_price;
                $user->tax_on_sale_price = $request->tax_on_sale_price;
                $user->mrp = $request->mrp;
                $user->hsn_code = $request->hsn_code;
                $user->gst_code = $request->gst_code;
                $user->on_portal = $request->on_portal;
                $user->pos_group_id = $request->pos_group_id;
                if ($request->hasFile('image1')) {
                    $file = $request->file('image1')->store('public/img/Product');
                    $user->image1 = $file;
                }
                if ($request->hasFile('image2')) {
                    $file = $request->file('image2')->store('public/img/Product');
                    $user->image2 = $file;
                }
                if ($request->hasFile('image3')) {
                    $file = $request->file('image3')->store('public/img/Product');
                    $user->image3 = $file;
                }
                if ($request->hasFile('image4')) {
                    $file = $request->file('image4')->store('public/img/Product');
                    $user->image4 = $file;
                }
                if ($request->hasFile('image5')) {
                    $file = $request->file('image5')->store('public/img/Product');
                    $user->image5 = $file;
                }
                $result = $user->save();
                $uom = new UomAssign();
                $uom->uom_id = $request->uom_id;
                $uom->product_id = $user->id;
                $uom->quantity = $request->uom_quantity;
                $uom->uom_2 = $request->uom_2;
                $uom->save();
                foreach($request->category as $category){
                    $assign = new Assigncategorie();
                    $assign->category_id = $category;
                    $assign->product_id = $user->id;
                    $assign->save();
                }
                foreach($request->subcategory as $subcategory){
                    $assign = new Assignsubcategorie();
                    $assign->subcategory_id = $subcategory;
                    $assign->product_id = $user->id;
                    $assign->save();
                }
                if ($result) {
                    $response = [
                                'Status' => true,
                                'message' => 'Product is successfully    created',
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function getProduct(Request $request){
            $rules =array(   
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = Product::get();
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'All Product Fetched',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function getProductById(Request $request)
        {
            $rules =array(
                "product_id" => "required"
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = Product::get();
                $data = [];
                foreach($user as $product){
                    $assigncat = Assigncategorie::where('product_id', $product->id)->get();
                    $category = [];
                    foreach($assigncat as $key){
                        $cat = Categorie::where('id',$key->category_id)->get();
                        $category[] = $cat;
                    }

                    $assignsubcat = Assignsubcategorie::where('product_id',$product->id)->get();
                    $subcategory = [];
                    foreach($assignsubcat as $key){
                        $subcat = Subcategorie::where('id',$key->subcategory_id)->get();
                        $subcategory = $subcat;
                    }
                    $uomtype = UomAssign::where('product_id',$product->id)->get();
                    $uom = [];
                    foreach($uom as $key){
                        $uomsam = Uom::where('id',$key->subcategory_id)->get();
                        $uom = $uomsam;
                    }
                    $data = array(
                        "product" => $product,
                        "category" => $category,
                        "subcategory" => $subcategory,
                        "uomassign" => $uomtype,
                        "uom" => $uom,
                    );
                }
                
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'All Product Fetched',
                                 'data' => $data,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function addChat(Request $request)
        {
            $rules =array(
                "sender" => "required",
                "receiver" => "required",
                "message" => "required",
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = new Chat();
                $user->sender = $request->sender;
                $user->receiver = $request->receiver;
                $user->messages = $request->message;
                $user->save();
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Message Sent',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function getConversion(Request $request)
        {
            $rules =array(
                "user1" => "required",
                "user2" => "required",
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = Chat::where('sender',$request->user1)->where('receiver',$request->user2)->orwhere('sender',$request->user2)->where('receiver',$request->user1)->get();
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'All MessAGE Fetched',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function createGroup(Request $request)
        {
            $rules =array(
                "userid" => "required",
                "name" => "required",
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = new Group();
                $user->name = $request->name;
                $user->created_by = $request->userid;
                $user->save();
                $user1 = new Member();
                $user1->group_id = $request->user->id;
                $user1->member_id = $request->userid;
                $user1->save();
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Group created successfully',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function getGroup(Request $request)
        {
            $rules =array(
                
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = Group::all();
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Group Fetched successfully',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function addMemberInGroup(Request $request)
        {
            $rules =array(
                "userid" => "required",
                "groupid" => "required",
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                if(Member::where('group_id',$request->groupid)->where('member_id',$request->userid)->first()){
                    return response(["status" => "error", "message" =>"Member Already in Group"], 401);
                }
                $user = new Member();
                $user->group_id = $request->groupid;
                $user->member_id = $request->userid;
                $user->save();
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Member Added In Group successfully',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function removeMemberFromGroup(Request $request)
        {
            $rules =array(
                "userid" => "required",
                "groupid" => "required",
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                if(!Member::where('group_id',$request->groupid)->where('member_id',$request->userid)->first()){
                    return response(["status" => "error", "message" =>"Member Not in Group"], 401);
                }
                $user = Member::where('group_id',$request->groupid)->where('member_id',$request->userid)->first()->delete();
               
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Member Removed',
                                //  'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function sendMessageInGroup(Request $request)
        {
            $rules =array(
                "userid" => "required",
                "groupid" => "required",
                "message" => "required"
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                if(!Member::where('group_id',$request->groupid)->where('member_id',$request->userid)->first()){
                    return response(["status" => "error", "message" =>"Member Not in Group"], 401);
                }
                $user = new Message();
                $user->group_id = $request->groupid;
                $user->member_id = $request->userid;
                $user->messages = $request->message;
                $user->save();
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Message sent successfully',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function getGroupById(Request $request)
        {
            $rules =array(
                "groupid" => "required",
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $group = Group::where('id', $request->groupid)->first();
                $members = Member::where('group_id', $request->groupid)->get();
                $messages = Message::where('group_id', $request->groupid)->get();
                $user = array(
                    "Group_Details" => $group,
                    "Members" => $members,
                    "Messages" => $messages
                );
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Group Fetched',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function addBlog(Request $request)
        {
            $rules =array(
                "userid" => "required",
                "content" => "required",
                "image" => "required",
                "caption" => "required",
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = new Blog();
                $user->caption = $request->caption;
                $user->content = $request->content;
                $user->user_id = $request->userid;
                if($request->hasFile('image'))
                $file = $request->file('image')->store('public/img/Blogs');
                $user->image = $file;
                $user->save();
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Group Fetched',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        
        public function getBlog(Request $request)
        {
            $rules =array(
                
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = Blog::all();
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Group Fetched',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function addBusinessCategory(Request $request)
        {
            $rules =array(
                "category_name" => "required",
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = new BusinessCategory();
                $user->category_name = $request->category_name;
            $user->save();
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Categorie was successfully created',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function addBusinessSubCategory(Request $request)
        {
            $rules =array(
                "sub_category_name" => "required",
                "category_id" => "required"
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = new BusinessSubCategory();
                $user->sub_category_name = $request->sub_category_name;
                $user->category_id = $request->category_id;
            $user->save();
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Sub Categorie was successfully created',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function getBusinessCategory(Request $request)
        {
            $rules =array(
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                if(isset($request->id)){
                $user = BusinessCategory::where('id',$request->id)->first();
                }else{
                $user = BusinessCategory::all();
                }
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Category Fetched',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function getBusinessSubCategory(Request $request)
        {
            $rules =array( 
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                if(isset($request->id)){
                $user = BusinessSubCategory::where('id',$request->id)->first();
                }else{
                $user = BusinessSubCategory::where('category_id',$request->category_id)->get();
                }
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Sub Category Fetched',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function addadvertisement(Request $request)
        {
            $rules =array(
                "company_id" => "required",
                "start_date" => "required",
                "end_date" => "required",
                "advertise_zone" => "required",
                "cities" => "required",
                "image" => "required",
                "payment_status" => "required",
                "payment_id" => "required",
                "payment_date" => "required"
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = new Advertisement();
                $user->payment_date = $request->payment_date;
                $user->payment_id = $request->payment_id;
                $user->payment_status = $request->payment_status;
                if ($request->hasFile('image')) {
                    $file = $request->file('image')->store('public/img/Advertisement');
                    $user->image  = $file;
                }
                $user->cities = $request->cities;
                $user->advertise_zone = $request->advertise_zone;
                $user->start_date = $request->start_date;
                $user->end_date = $request->end_date;
                $user->company_id = $request->company_id;
                $user->save();
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Advertisement Submitted For Review.',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function getAdvertisement(Request $request)
        {
            $rules =array(
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                if(isset($request->id)){
                $user = Advertisement::where('id',$request->id)->where('is_approved','1')->first();
                }else{
                $user = Advertisement::where('is_approved','1')->get();
                }
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Active Advertisement Fetched',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function addpackage(Request $request)
        {
            $rules =array(
                "package_name" => "required",
                "price" => "required",
                "validity" => "required",
                "start_date" => "required",
                "end_date" => "required"
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = new Packages();
                $user->start_date = $request->start_date;
                $user->end_date = $request->end_date;
                $user->package_name = $request->package_name;
                $user->price = $request->price;
                $user->validity = $request->validity;
                $user->save();
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Package Created Successfully',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function getPackage(Request $request)
        {
            $rules =array(
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                if(isset($request->id)){
                $user = Packages::where('id',$request->id)->first();
                }else{
                $user = Packages::all();
                }
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Packages Fetched',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function adduoms(Request $request)
        {
            $rules =array(
                "name" => "required",
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = new Uom();
                $user->name = $request->name;
                $user->save();
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Uom Created',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function getUoms(Request $request)
        {
            $rules =array(
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                if(isset($request->id)){
                $user = Uom::where('id',$request->id)->first();
                }else{
                $user = Uom::all();
                }
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Uoms Fetched',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function addFactoryAddress(Request $request)
        {
            $rules =array(
                "address" => "required",
                "city" => "required",
                "state" => "required",
                "country" => "required",
                "user_id" => "required",
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = new FactoryAddress();
                $user->address = $request->address;
                $user->city = $request->city;
                $user->country = $request->country;
                $user->state = $request->state;
                $user->pin_code = $request->pin_code;
                $user->user_id = $request->user_id;
                $user->save();
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Factory Address Created',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function getFactoryByUser(Request $request)
        {
            $rules =array(
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                if(isset($request->userid)){
                $user = FactoryAddress::where('user_id',$request->userid)->get();
                }
                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Factory Address Fetched',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function loginMember(Request $request){
        $rules =array(
            "mobile" => "required",
            "password" => "required|min:6"
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else {
                $mobile = substr($request->mobile, -10);
                if(!Managers::where('mobile',$mobile)->first()){
                    return response(["status" =>"failed", "message"=>"User is not Registered or Invaild User Type"], 401);
                }
                $user = Managers::where('mobile',$request->mobile)->first();
                if(!Hash::check($request->password,$user->password)){
                    return response(["status" =>"failed", "message"=>"Incorrect Password"], 401);
                }
            }
            if ($user){
                $response = [
                'user' => $user,
                "message"=>"User is Logged IN"
            ];
                return response($response, 200);
            }
        }
        public function registerMember(Request $request){
            $rules =array( 
                "name" => "required",
                "mobile" => "required",
                "password" => "required|min:6",    
                "profile" => "required",       
                "company_id" => "required",
                "desk_id" => "required"
                );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $mobile = substr($request->mobile, -10);
                if(Managers::where('mobile',$mobile)->first()){
                    return response(["status" =>"failed", "message"=>"Already Registered"], 401);
                }
                $user = new Managers();
                $user->name = $request->name;
                $user->mobile = substr($request->mobile,-10);
                $user->password = Hash::make($request->password);
                $user->company_id = $request->company_id;
                $user->desk_id = $request->desk_id;
                if($request->hasFile('profile')){
                    $file = $request->file('profile')->store('public/img/Managerprofile');
                    $user->profile = $file;
                }
                $result= $user->save();
                if ($result) {
                    $response = [
                    'message' => 'Company Manager Created Successfully',
                    'user' => $user
                ];
                    return response($response, 200);
                }
                else
                {
                    return response(["status" =>"failed", "message"=>"User is not created"], 401);
                }
            }
        }
        public function addPayment(Request $request)
        {
            $rules =array(
                "user_id" => "required",
                "package_code" => "required",
                "payment_status" => "required",
                "validity_date" =>  "required",
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = User::where('id',$request->user_id)->first();
                $user->package_code = $request->package_code;
                $user->is_subscribed = 1;
                $user->payment_status = $request->payment_status;
                $user->validity_date = $request->validity_date;
            $user->save();

                if ($user) {
                    $response = [
                                 'Status' => true,
                                 'message' => 'Package Added successfully In Users Id',
                                 'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        function getUserId(Request $request){
            $rules =array(
                "user_id" => "required"
            );
            $validator= Validator::make($request->all(),$rules);
            if($validator->fails()){
                return $validator->errors();
            }
            else{
            $user = User::find($request->user_id);
                if($user){
                    $category = BusinessCategoryAssign::where('user_id', $user->id)->get('business_category_id');
                    $subcategory = BusinessSubCategoryAssign::where('user_id', $user->id)->get('business_sub_category_id');
                    $follower = Follower::where('follow',$user->id)->get()->count();
                    $following = Follower::where('followed',$user->id)->get()->count();
                    return response(["status" => "Sucess","message" => "user fetched","data" => $user,"follower"=>$follower,"following"=>$following,
                    "business_category"=>$category,
                    "business_sub_category"=>$subcategory,
                    ], 200);
                }
                else{
                    return response(["status" => "failed","message" => "Invalid OTP"], 200);
                }
            }
        }
        function getAllUser(Request $request){
            $rules =array(
            );
            $validator= Validator::make($request->all(),$rules);
            if($validator->fails()){
                return $validator->errors();
            }
            else{
            $user = User::all();
                if($user){
                    return response(["status" => "Sucess","message" => "user fetched","data" => $user
                    ], 200);
                }
                else{
                    return response(["status" => "failed","message" => "Invalid"], 200);
                }
            }
        }
}