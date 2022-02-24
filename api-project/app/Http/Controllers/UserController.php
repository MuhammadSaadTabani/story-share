<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BlockUser;
use App\Models\ChatImage;
// use Dotenv\Validator;
// use Exception;
use Facade\FlareClient\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Response as FacadeResponse;
use DB;

class UserController extends Controller
{
    
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        // dd('here');
        // $this->middleware('auth:api');
    }

    public function register(Request $request)
    {
        $checkEmail = User::where('email','=', $request['email'])->first();
        if($checkEmail != null){

               //User created, return success response
            return response()->json([
                'success' => false,
                'message' => 'Email Already Exist'
            ]);

        }else{
            //Request is valid, create new user
            $user = User::create([
                'username' => $request->username,
                'full_name' => $request->full_name,
                'dob' => $request->dob,
                'email' => $request->email,
                'password' => bcrypt($request->password)
            ]);

            //User created, return success response
            return response()->json([
                'success' => true,
                'message' => 'User created successfully'
            ]);
        }        
    }
 
    public function authenticate(Request $request)
    {
        $credentials = $request->only('email', 'password');

        //Facebook Login Start
        if($request->fbid != ""){
            $checkUser = User::select('id', 'fbid', 'username', 'full_name', 'email', 'image', 'cover_image', 'status', 'created_at')->where('fbid', $request->fbid)->first();
            //Facebook Login / Register Start
            if($checkUser != null){ // Facebook login in if condition
                try {
                    if (! $token = JWTAuth::fromUser($checkUser)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Login credentials are invalid.',
                        ]);
                    }
                } catch (JWTException $e) {
                    return $credentials;
                    return response()->json([
                            'success' => false,
                            'message' => 'Could not create token.',
                        ], 500);
                }
                
                if($checkUser->image != ""){

                    // $imageFullUrl =  url('images/'). '/' . $checkUser->image;
                    $imageFullUrl =  $checkUser->image;
                }else{
                    $imageFullUrl =  "";
                }

                if($checkUser->cover_image != ""){

                    // $coverImageFullUrl =  url('cover_images/'). '/' . $checkUser->cover_image;
                    $coverImageFullUrl =  $checkUser->cover_image;
                }else{
                    $coverImageFullUrl =  "";
                }
                
                
                $arrayUserData = array(
                    'id'            => $checkUser->id,
                    'username'      => $checkUser->username,
                    'full_name'     => $checkUser->full_name,
                    'email'         => $checkUser->email,
                    'dob'           => $checkUser->dob,
                    'image'         => $imageFullUrl,
                    'cover_image'   => $coverImageFullUrl,
                    'status'        => $checkUser->status,
                    'created_at'    => $checkUser->created_at
                );
                
                //Token created, return with success response and jwt token
                return response()->json([
                    'success'       => true,
                    'message'       => 'Login Successfully',
                    'token'         => $token,
                    'userData'      => $arrayUserData,
                    'isSubscribe'      => ($checkUser->latest_subscription)?($checkUser->latest_subscription->is_active==1?true:false):false
                ]);            
                
            }else{ //Facebook register and login after register
                $fbRegister['fbid'] = $request->fbid;
                $fbRegister['full_name'] = $request->name;
                $registerUser = User::create($fbRegister);
                
                if($registerUser){
                    $checkUser = User::select('id', 'fbid', 'username', 'full_name', 'email', 'image', 'cover_image', 'status', 'created_at')->where('fbid', $request->fbid)->first();
                    try {
                        if (! $token = JWTAuth::fromUser($checkUser)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Login credentials are invalid.',
                            ]);
                        }
                    } catch (JWTException $e) {
                        return $credentials;
                        return response()->json([
                                'success' => false,
                                'message' => 'Could not create token.',
                            ], 500);
                    }
                    
                    if($checkUser->image != ""){
    
                        // $imageFullUrl =  url('images/'). '/' . $checkUser->image;
                        $imageFullUrl =  $checkUser->image;
                    }else{
                        $imageFullUrl =  "";
                    }
    
                    if($checkUser->cover_image != ""){
    
                        // $coverImageFullUrl =  url('cover_images/'). '/' . $checkUser->cover_image;
                        $coverImageFullUrl =   $checkUser->cover_image;
                    }else{
                        $coverImageFullUrl =  "";
                    }
                    
                    
                    $arrayUserData = array(
                        'id'            => $checkUser->id,
                        'username'      => $checkUser->username,
                        'full_name'     => $checkUser->full_name,
                        'email'         => $checkUser->email,
                        'dob'           => $checkUser->dob,
                        'image'         => $imageFullUrl,
                        'cover_image'   => $coverImageFullUrl,
                        'status'        => $checkUser->status,
                        'created_at'    => $checkUser->created_at
                    );
                    
                    //Token created, return with success response and jwt token
                    return response()->json([
                        'success'       => true,
                        'message'       => 'Login Successfully',
                        'token'         => $token,
                        'userData'      => $arrayUserData,
                        'isSubscribe'      => ($checkUser->latest_subscription)?($checkUser->latest_subscription->is_active==1?true:false):false
                    ]);      
                }else{
                    return response()->json([
                        'success'       => false,
                        'message'       => 'Failed to login'
                    ]);
                }
            }//Facebook Login / Register End

        }else if($request->appleid != ""){ // Facebook login end (In If Condition) ==== Apple login start (In Else If Condition)
            $checkUser = User::select('id', 'appleid', 'username', 'full_name', 'email', 'image', 'cover_image', 'status', 'created_at')->where('appleid', $request->appleid)->first();
            //Apple Login / Register Start
            if($checkUser != null){ // Apple login in if condition
                try {
                    if (! $token = JWTAuth::fromUser($checkUser)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Login credentials are invalid.',
                        ]);
                    }
                } catch (JWTException $e) {
                    return $credentials;
                    return response()->json([
                            'success' => false,
                            'message' => 'Could not create token.',
                        ], 500);
                }
                
                if($checkUser->image != ""){

                    // $imageFullUrl =  url('images/'). '/' . $checkUser->image;
                    $imageFullUrl =   $checkUser->image;
                }else{
                    $imageFullUrl =  "";
                }

                if($checkUser->cover_image != ""){

                    // $coverImageFullUrl =  url('cover_images/'). '/' . $checkUser->cover_image;
                    $coverImageFullUrl =   $checkUser->cover_image;
                }else{
                    $coverImageFullUrl =  "";
                }
                
                
                $arrayUserData = array(
                    'id'            => $checkUser->id,
                    'username'      => $checkUser->username,
                    'full_name'     => $checkUser->full_name,
                    'email'         => $checkUser->email,
                    'dob'           => $checkUser->dob,
                    'image'         => $imageFullUrl,
                    'cover_image'   => $coverImageFullUrl,
                    'status'        => $checkUser->status,
                    'created_at'    => $checkUser->created_at
                );
                
                //Token created, return with success response and jwt token
                return response()->json([
                    'success'       => true,
                    'message'       => 'Login Successfully',
                    'token'         => $token,
                    'userData'      => $arrayUserData,
                    'isSubscribe'      => ($checkUser->latest_subscription)?($checkUser->latest_subscription->is_active==1?true:false):false
                ]);            
                
            }else{ //Apple register and login after register
                $appleRegister['appleid'] = $request->appleid;
                $appleRegister['full_name'] = $request->name;
                $registerUser = User::create($appleRegister);
                
                if($registerUser){
                    $checkUser = User::select('id', 'appleid', 'username', 'full_name', 'email', 'image', 'cover_image', 'status', 'created_at')->where('appleid', $request->appleid)->first();
                    try {
                        if (! $token = JWTAuth::fromUser($checkUser)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Login credentials are invalid.',
                            ]);
                        }
                    } catch (JWTException $e) {
                        return $credentials;
                        return response()->json([
                                'success' => false,
                                'message' => 'Could not create token.',
                            ], 500);
                    }
                    
                    if($checkUser->image != ""){
    
                        // $imageFullUrl =  url('images/'). '/' . $checkUser->image;
                        $imageFullUrl =   $checkUser->image;
                    }else{
                        $imageFullUrl =  "";
                    }
    
                    if($checkUser->cover_image != ""){
    
                        // $coverImageFullUrl =  url('cover_images/'). '/' . $checkUser->cover_image;
                        $coverImageFullUrl =   $checkUser->cover_image;
                    }else{
                        $coverImageFullUrl =  "";
                    }
                    
                    $arrayUserData = array(
                        'id'            => $checkUser->id,
                        'username'      => $checkUser->username,
                        'full_name'     => $checkUser->full_name,
                        'email'         => $checkUser->email,
                        'dob'           => $checkUser->dob,
                        'image'         => $imageFullUrl,
                        'cover_image'   => $coverImageFullUrl,
                        'status'        => $checkUser->status,
                        'created_at'    => $checkUser->created_at
                    );
                    
                    //Token created, return with success response and jwt token
                    return response()->json([
                        'success'       => true,
                        'message'       => 'Login Successfully',
                        'token'         => $token,
                        'userData'      => $arrayUserData,
                        'isSubscribe'      => ($checkUser->latest_subscription)?($checkUser->latest_subscription->is_active==1?true:false):false
                    ]);      
                }else{
                    return response()->json([
                        'success'       => false,
                        'message'       => 'Failed to login'
                    ]);
                }
            }//Apple Login / Register End

        }else{ //Apple Login End (In else If condition) ==== (In Else Condtion) Normal login start
            //Request is validated
            //Create token
            try {
                if (! $token = JWTAuth::attempt($credentials)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Login credentials are invalid.',
                    ]);
                }
            } catch (JWTException $e) {
                return $credentials;
                return response()->json([
                        'success' => false,
                        'message' => 'Could not create token.',
                    ], 500);
            }
        
            // $getUserData = User::select('id', 'username', 'firebase_id','full_name', 'email', 'image', 'cover_image', 'status', 'created_at')->where('email','=', $credentials['email'])->first();
            $getUserData = User::select('id', 'username', 'full_name', 'email', 'image', 'cover_image', 'status', 'created_at')->where('email','=', $credentials['email'])->first();
            
            if($getUserData === null){
                
                return response()->json([
                    'success'       => false,
                    'message'       => 'Email not exists'
                ]);
                
            }else{
                    
                if($getUserData->image != ""){

                    // $imageFullUrl =  url('images/'). '/' . $getUserData->image;
                    $imageFullUrl =  $getUserData->image;
                }else{
                    $imageFullUrl =  "";
                }

                if($getUserData->cover_image != ""){

                    // $coverImageFullUrl =  url('cover_images/'). '/' . $getUserData->cover_image;
                    $coverImageFullUrl =   $getUserData->cover_image;
                }else{
                    $coverImageFullUrl =  "";
                }
                
                $arrayUserData = array(
                    'id'            => $getUserData->id,
                    'username'      => $getUserData->username,
                    'full_name'     => $getUserData->full_name,
                    'email'         => $getUserData->email,
                    'dob'           => $getUserData->dob,
                    'image'         => $imageFullUrl,
                    'cover_image'   => $coverImageFullUrl,
                    'status'        => $getUserData->status,
                    'created_at'    => $getUserData->created_at
                );
                
                //Token created, return with success response and jwt token
                return response()->json([
                    'success'       => true,
                    'message'       => 'Login Successfully',
                    'token'         => $token,
                    'userData'      => $arrayUserData,
                    'isSubscribe'      => ($getUserData->latest_subscription)?($getUserData->latest_subscription->is_active==1?true:false):false
                ]);            
            }
        }// Normal login end
        
    }
 
    public function logout(Request $request)
    {
        //valid credential
        $validator = Validator::make($request->only('token'), [
            'token' => 'required'
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

		//Request is validated, do logout        
        try {
            JWTAuth::invalidate($request->token);
 
            return response()->json([
                'success' => true,
                'message' => 'User has been logged out'
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, user cannot be logged out'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
 
    public function get_user(Request $request)
    {
        $this->validate($request, [
            'token' => 'required'
        ]);
 
        $user = JWTAuth::authenticate($request->token);

        return response()->json(['user' => $user]);
    }

    //
    public function emailRegistration(Request $request)
    {
        // print_r(env('MAIL_HOST'));
        // print_r(env('MAIL_PORT'));
        // print_r(env('MAIL_USERNAME'));
        // print_r(env('MAIL_PASSWORD'));
        // print_r(env('MAIL_FROM_ADDRESS'));
        // die;
        $postData       = $request->all();
        $emailAddress   = $postData['email'];
        //Check email in database if it's exist or not 
        $checkEmailExist = User::where('email','=', $emailAddress)->first();
        if($checkEmailExist == null){
            //generate six digits code
            $six_digit_random_number = mt_rand(1000, 9999);

            $registerEmail = User::create([
                'email' => $emailAddress,
                'code'  => $six_digit_random_number
                // 'code'  => '1234'
            ]);

            if($registerEmail)
            {
                // $data = array('code'=> $six_digit_random_number);
                // Mail::send('email_verification', $data, function($message) use ($emailAddress) {
                // $message->to($emailAddress)
                // ->subject('Email Verification Code');
                // $message->from('info@projects.paragonlogo.com','Salvador App');
                // });
                
                $to = $emailAddress;
                $subject = "Email Verification Code - Story Share";
                
                $message = "
                <html>
                <head>
                <title>Email</title>
                </head>
                <body>
                <p>Your Code ". $six_digit_random_number ."</p>
                </body>
                </html>
                ";
                
                // Always set content-type when sending HTML email
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                
                // More headers
                $headers .= 'From: <info@projects.paragonlogo.com>' . "\r\n";
                $headers .= 'Cc: info@projects.paragonlogo.com' . "\r\n";
                
                mail($to,$subject,$message,$headers);

                return response([
                    'success' => true,
                    'message' => 'Successfully Code generated'
                ]);
            }else{
                return response([
                    'success' => false,
                    'message' => 'Failed to generate code'
                ]);
            }
        }else{
            return response([
                'success' => false,
                'message' => 'Email already exists'
            ]);
        }
    }



    public function checkCodeEmailVerification(Request $request)
    {
        $postData       = $request->all();
        $code           = $postData['code'];
        $emailAddress   = $postData['email'];
        $checkEmailCode = User::where('email','=', $emailAddress)->where('code','=', $code)->first();
        if($checkEmailCode == null)
        {
            return response([
                'success' => false,
                'message' => 'Invalid Code'
            ]);
        }else{
            $updateUserVerify = User::where('email','=', $emailAddress)
                            ->update([
                                'email_verify'      => 'yes',
                                'status'            => 'active',
                                'email_verified_at' => date('Y-m-d')
                            ]);
            if($updateUserVerify){
                return response([
                    'success'   => true,
                    'message'   => 'Successful',
                    'data'      => [
                        'id'    => $checkEmailCode->id,
                        'email' => $checkEmailCode->email
                    ]
                ]);
            }else{
                return response([
                    'success'   => false,
                    'message'   => 'Failed to update email verification'
                ]);
            }
            
        }
    }

    public function ForgetPasswordEmail(Request $request)
    {
        $postData       = $request->all();
        $emailAddress   = $postData['email'];
        //Check email in database if it's exist or not 
        $checkEmailExist = User::where('email','=', $emailAddress)->first();
        if($checkEmailExist != null){
            //generate six digits code
            $six_digit_random_number = mt_rand(1000, 9999);

            $setCode = User::where('email', '=', $emailAddress)->update([
                'code'  => '1234'
            ]);

            if($setCode)
            {
                // $data = array('code'=> $six_digit_random_number);
                // Mail::send('email_verification', $data, function($message) use ($emailAddress) {
                // $message->to($emailAddress)
                // ->subject('Email Verification Code');
                // $message->from('info@projects.paragonlogo.com','Salvador App');
                // });

                // $to = $emailAddress;
                // $subject = "Email Verification Code - Story Share";
                
                // $message = "
                // <html>
                // <head>
                // <title>Email</title>
                // </head>
                // <body>
                // <p>Your Code ". $six_digit_random_number ."</p>
                // </body>
                // </html>
                // ";
                
                // // Always set content-type when sending HTML email
                // $headers = "MIME-Version: 1.0" . "\r\n";
                // $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                
                // // More headers
                // $headers .= 'From: <info@projects.paragonlogo.com>' . "\r\n";
                // $headers .= 'Cc: info@projects.paragonlogo.com' . "\r\n";
                
                // mail($to,$subject,$message,$headers);

                return response([
                    'success' => true,
                    'message' => 'Successfully Code generated',
                    'data'      => [
                        'id'    => $checkEmailExist->id,
                        'email' => $checkEmailExist->email
                    ]
                ]);
            }else{
                return response([
                    'success' => false,
                    'message' => 'Failed to generate code'
                ]);
            }
        }else{
            return response([
                'success' => false,
                'message' => 'Email not exists'
            ]);
        }
    }
    
    public function checkForgetPasswordCodeVerification(Request $request)
    {
        $postData       = $request->all();
        $code           = $postData['code'];
        $id             = $postData['id'];
        $checkEmailCode = User::where('id','=', $id)->where('code','=', $code)->first();
        if($checkEmailCode == null)
        {
            return response([
                'success' => false,
                'message' => 'Invalid Code'
            ]);
        }else{
            
            return response([
                'success'   => true,
                'message'   => 'Successful'
            ]);
            
        }
    }
    
    public function updateForgetPassword(Request $request)
    {
        $postData       = $request->all();
        $id           = $postData['id'];
        $password   = $postData['password'];
        $passwordHash = Hash::make($password);
        $updatePassword = User::where('id','=', $id)->update(['password' => $passwordHash]);
        if($updatePassword)
        {
            return response([
                'success'   => true,
                'message'   => 'Successful'
            ]);
            
        }else{
            return response([
                'success' => false,
                'message' => 'Failed'
            ]);
        }
    }
    
    public function changePassword(Request $request)
    {
        $postData       = $request->all();
        $currentPassword = $postData['currentPassword'];
        $password   = $postData['password'];
        $passwordHash = Hash::make($password);
        $user = JWTAuth::user();
        $idUser = $user['id'];
        $checkPassword = User::where('id','=', $idUser)->get();
        if(count($checkPassword) > 0){
            $gethashedPassword = $checkPassword[0]->password;
            if (Hash::check($currentPassword, $gethashedPassword)) {
                // The passwords match...
                $updatePassword = User::where('id','=', $idUser)->update(['password' => $passwordHash]);
                if($updatePassword)
                {
                    return response([
                        'success'   => true,
                        'message'   => 'Successful'
                    ]);
                    
                }else{
                    return response([
                        'success' => false,
                        'message' => 'Failed'
                    ]);
                }
            }else{
                return response([
                    'success' => false,
                    'message' => 'Current Password Incorrect'
                ]);
            }
        }
        // print_r($user['id']);die;
        
    }
    
    public function getPrivacyPolicy()
    {
        $getPrivacyPolicy = \DB::table('pages')->where('Title','=','Privacy Policy')->get();
        // dd($getPrivacyPolicy);
        return response([
            'success' => true,
            'PrivacyPolicy' => $getPrivacyPolicy[0]->Content
        ]);
    }
    
    public function getTermsAndConditions()
    {
        $getTermsAndConditions = \DB::table('pages')->where('Title','=','Terms & Conditions')->get();
        // dd($getTermsAndConditions);
        return response([
            'success' => true,
            'TermsAndConditions' => $getTermsAndConditions[0]->Content
        ]);
    }
    
    public function updateProfile(Request $request)
    {
        $postData           = $request->all();
        $username           = $postData['username'];
        $full_name          = $postData['full_name'];
        $dob          = $postData['dob'];
        
        $getUserDetails = JWTAuth::user();
        $userId = $getUserDetails['id'];
        
        $getUser = User::where('id','=', $userId)->get();
        if(count($getUser) > 0)
        {
            if($request->hasFile('image')){
                $fileName = time(). '-'. rand() . '.'.$request->image->extension();  
                $request->image->move(public_path('images'), $fileName);
                $imagePath = url('images').'/'. $fileName;    
        
            }else{
                $getOldImageName = $getUser[0]->image;
                $imagePath = $getOldImageName;
            }
            if($request->hasFile('cover_image')){
                $fileName = time(). '-'. rand() . '.'.$request->cover_image->extension();  
                $request->cover_image->move(public_path('cover_images'), $fileName);
                $CoverImagePath = url('cover_images').'/'. $fileName;    
            }else{
                $getOldCoverImageName = $getUser[0]->cover_image;
                $CoverImagePath = $getOldCoverImageName;
            }
    
            $updateUserProfile  = User::where('id','=', $userId)
            ->update([
                'username'      => $username,
                'full_name'     => $full_name,
                'dob'           => $dob,
                'image'         => $imagePath,
                'cover_image'   => $CoverImagePath,
                
            ]);
        }else{
            return response([
                'success'   => false,
                'message'   => 'User not exists'
            ]);
        }

        
        
        if($updateUserProfile)
        {
            $getUserData = User::where('id','=', $userId)->first();
            
            $arrayUserData = array(
                'id'            => $getUserData->id,
                'username'      => $getUserData->username,
                'full_name'     => $getUserData->full_name,
                'email'         => $getUserData->email,
                'dob'           => $getUserData->dob,
                'image'         => $imagePath,
                'cover_image'         => $CoverImagePath,
                'status'        => $getUserData->status,
                'created_at'    => $getUserData->created_at
            );
                    
            if($getUserData != null){
                return response([
                    'success'   =>  true,
                    'message'   => 'Successfully updated',
                    'userData'  => $arrayUserData
                ]);
            }else{
                return response([
                    'success'   => false,
                    'message'   => 'Failed'
                ]);
            }
        }
    }

    public function compressImage(Request $request)
    {
        $postData = $request->all();

        $image = $request->file('imageData');
        
        
        $postData['imagename'] = time().'.'.$image->extension();
     
        $destinationPath = public_path('compress_images');
        // dd($image->path());
        // $image->path()
       try{
           
       
            $img = \Image::make($image->path());
            // dd($img);
            $img->resize(100, 100, function ($constraint) {
                $constraint->aspectRatio();
            })->save($destinationPath.'/'.$postData['imagename']);
       
            $destinationPath = public_path('/imgg');
            $image->move($destinationPath, $postData['imagename']);
    
            
            $imagePath = url('compress_images/') . '/' . $postData['imagename'];
    
            // $b64image = 'data:image/jpg;base64,'.base64_encode(file_get_contents($imagePath));
       }catch(Exception $e){
           
            report($e);
       }
        return response([
            'success'   =>  true,
            'message'   => 'Successful',
            'imageUrl'  => $imagePath
            // 'b64Image'  => $b64image
        ]);    
    }
    
    public function searchFilter(Request $request)
    {
        //$postData = $request->all();
        //return auth()->id();
        //if(!isset($_POST['searchText']) || $_POST['searchText'] == ""){ // Problem 
        if(!isset($request->searchText) || $request->searchText == ''){
            $data = User::where('id', '!=', auth()->id())->where('email_verify', 'yes')->inRandomOrder()->limit(30)->get();
        }else{
            $searchText = $request->searchText;
            //if(preg_match("/[a-z]/i", $searchText)){  // Problem 
                $data = User::where('id', '!=', auth()->id())
                            ->where('email_verify', 'yes')
                            ->where('username', 'LIKE', '%'.$request->searchText.'%')
                            ->orWhere('phone_number', 'LIKE', '%'.$request->searchText.'%')
                            ->get()->toArray();
                //$data = 'else';
            //}
            //else{
               //$data = User::where('id', '!=', auth()->id())->where('phone_number', 'LIKE', '%'.$searchText.'%')->get();
            //}
        }
        
        foreach($data as $dkey => $dat){
            $data[$dkey]['image'] = url('images').'/'.$data[$dkey]['image'];
        }
        return response([
            'success' => true,
            'message'   => 'Successful',
            'data' => $data
        ]);
    }
}
