<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * Get a JWT token via given credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if ($token = $this->guard()->attempt($credentials)) {
            return $this->respondWithToken($token);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json($this->guard()->user());
    }

    /**
     * Log the user out (Invalidate the token)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ]);
    }

    public function emailRegistration(Request $request)
    {
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
            ]);

            if($registerEmail)
            {
                $data = array('code'=> $six_digit_random_number);
                Mail::send('email_verification', $data, function($message) use ($emailAddress) {
                $message->to($emailAddress)
                ->subject('Email Verification Code');
                $message->from('hamza.akbar15@gmail.com','Salvador App');
                });

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

    public function updateUserProfile(Request $request, $id, Exception $exception)
    {

        if ($exception instanceof UnauthorizedHttpException) {
            if($exception instanceof TokenInvalidException){
                return response()->json(['error' => 'Token is Invalid'], 400);
            }elseif($exception instanceof TokenExpiredException){
                return response()->json(['error' => 'Token is Expired'], 400);
            }elseif($exception instanceof JWTException){
                return response()->json(['error' => 'There is problem with your token'], 400);
            }

            if ($exception->getMessage() === 'Token not provided') {
                return response()->json(['error' => 'Token not provided']);
            }
        }else{
            $postData           = $request->all();
            // dd($request->image);
            // dd($_FILES['image']);
            // dd('here2');
            
            $username           = $postData['username'];
            $full_name          = $postData['full_name'];
            $email              = $postData['email'];
            $password           = $postData['password'];
            $phone_number       = $postData['phone_number'];

            $passwordHash = Hash::make($password);

            
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
        
            $imageName = time().'.'.$request->image->extension();  
        
            $request->image->move(public_path('images'), $imageName);
        
            $imagePath = url('image').'/'. $imageName;

            $updateUserProfile  = User::where('id','=', $id)
                            ->update([
                                'username'      => $username,
                                'full_name'     => $full_name,
                                'email'         => $email,
                                'password'      => $passwordHash,
                                'phone_number'  => $phone_number,
                                'image'         => $imageName
                            ]);

            if($updateUserProfile)
            {
                $getUserData = User::where('id','=', $id)->first();
                if($getUserData != null){
                    return response([
                        'success'   =>  true,
                        'message'   => 'Successfully updated',
                        'data'      => [
                                        'id'            => $getUserData->id,
                                        'username'      => $getUserData->username, 
                                        'full_name'     => $getUserData->full_name, 
                                        'email'         => $getUserData->email, 
                                        'phone_number'  => $getUserData->phone_number, 
                                        'image'         => $imagePath
                                    ]
                    ]);
                }else{
                        return response([
                        'success'   => false,
                        'message'   => 'Failed'
                    ]);
                }
            }
        }

    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard()
    {
        return Auth::guard();
    }
}
