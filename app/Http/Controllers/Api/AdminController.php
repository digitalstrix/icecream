<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
}