<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assigncategorie;
use App\Models\Assignsubcategorie;
use App\Models\Blog;
use App\Models\Categorie;
use App\Models\Chat;
use App\Models\Follower;
use App\Models\Group;
use App\Models\Like;
use App\Models\Member;
use App\Models\Message;
use App\Models\Newe;
use App\Models\Newscategorie;
use App\Models\Post;
use App\Models\Product;
use App\Models\Subcategorie;
use App\Models\User;
use App\Notifications\Authentication;
use Dirape\Token\Token;
use Exception;
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
                $user->mobile = $request->mobile;
                $user->user_type ='admin';
                $user->password =Hash::make($request->password);
                $token_qr = (new Token())->Unique('users', 'user_token', 60);
                $user->user_token = $token_qr;
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
                $follower = Follower::where('follow',$user->id)->get()->count();
                $following = Follower::where('followed',$user->id)->get()->count();
                $token = $user->createToken('my-app-token')->plainTextToken;
                return response(["status" => "Sucess","message" => "Login Successfully","data" => $user,"follower"=>$follower,"following"=>$following,"token" => $token], 200);
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
            if(isset($request->password)) {
                $user->password =Hash::make($request->password);
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
                "image" => "required"
             );
            $validator= Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            } else {
                $user = new Product();
                $user->name = $request->name;
                $user->description = $request->description;
                if($request->hasFile('image'))
                $file = $request->file('image')->store('public/img/category');
                $user->image = $file;
                $result = $user->save();
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
                                 'message' => 'Product is successfully   created',
                                //  'data' => $user,
                ];
                    return response($response, 201);
                } else {
                    return response(["status" => "error", "message" =>"Categorie is not created"], 401);
                }
            }
        }
        public function getProduct(Request $request)
        {
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
                    $data = array(
                        "product" => $product,
                        "category" => $category,
                        "subcategory" => $subcategory
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
        
        
        
        
}