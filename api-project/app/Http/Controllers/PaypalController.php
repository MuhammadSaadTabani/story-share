<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

use App\Models\Subscription;
use App\Models\User;

class PaypalController extends Controller
{
    public function createSubscription(Request $request)
    {
        
        $d = date('Y-m-d');
        $currentDate = date('Y-m-d', strtotime( $d . " +1 days"));
        $datee = $currentDate .'T00:00:00Z';

        $userId = JWTAuth::user()->id;
        $userFullName = JWTAuth::user()->full_name; //getting user fullName from jwtAuth
        $userEmail = JWTAuth::user()->email; //getting user email from jwtAuth

        // dd($userEmail);
        // $curl = curl_init();

        // curl_setopt_array($curl, array(
        //     CURLOPT_URL => 'https://api-m.sandbox.paypal.com/v1/billing/subscriptions',
        //     CURLOPT_RETURNTRANSFER => true,
        //     CURLOPT_ENCODING => '',
        //     CURLOPT_MAXREDIRS => 10,
        //     CURLOPT_TIMEOUT => 0,
        //     CURLOPT_FOLLOWLOCATION => true,
        //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //     CURLOPT_CUSTOMREQUEST => 'POST',
        //     CURLOPT_POSTFIELDS =>'{
        //         "plan_id": "P-01L69754N2822245NMIAYD7I",
        //         "start_time": "'.$currentDate.'T00:00:00Z",
        //         "quantity": "1",
        //         "shipping_amount": {
        //             "currency_code": "USD",
        //             "value": "0.00"
        //         },
        //         "subscriber": {
        //             "name": {
        //             "given_name": "'. $userFullName .'",
        //             "surname": "'. $userFullName .'"
        //             },
        //             "email_address": "'. $userEmail .'",
        //             "shipping_address": {
        //                 "name": {
        //                     "full_name": "'. $userFullName .'"
        //                 },
        //                 "address": {
        //                     "address_line_1": "2211 N First Street",
        //                     "address_line_2": "Building 17",
        //                     "admin_area_2": "San Jose",
        //                     "admin_area_1": "CA",
        //                     "postal_code": "95131",
        //                     "country_code": "US"
        //                 }
        //             }
        //         },
        //         "application_context": {
        //             "brand_name": "storyshare",
        //             "locale": "en-US",
        //             "shipping_preference": "SET_PROVIDED_ADDRESS",
        //             "user_action": "SUBSCRIBE_NOW",
        //             "payment_method": {
        //                 "payer_selected": "PAYPAL",
        //                 "payee_preferred": "IMMEDIATE_PAYMENT_REQUIRED"
        //             },
        //             "return_url": "https://example.com/returnUrl",
        //             "cancel_url": "https://example.com/cancelUrl"
        //         }
        //     }',
        //     CURLOPT_HTTPHEADER => array(
        //         'Authorization: Bearer '. $request->paypalToken
        //     ),
        // ));

        // $response = curl_exec($curl);

        // curl_close($curl);
        // echo $response;

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api-m.paypal.com/v1/billing/subscriptions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
        "plan_id": "P-01L69754N2822245NMIAYD7I",
        "start_time": "'. $datee .'",
        "quantity": "1",
        "shipping_amount": {
            "currency_code": "USD",
            "value": "0.00"
        },
        "subscriber": {
            "name": {
            "given_name": "'. $userFullName .'",
            "surname": "'. $userFullName .'"
            },
            
            "shipping_address": {
            "name": {
                "full_name": "'. $userFullName .'"
            },
            "address": {
                "address_line_1": "2211 N First Street",
                "address_line_2": "Building 17",
                "admin_area_2": "San Jose",
                "admin_area_1": "CA",
                "postal_code": "95131",
                "country_code": "US"
            }
            }
        },
        "application_context": {
            "brand_name": "Story Share",
            "locale": "en-US",
            "shipping_preference": "SET_PROVIDED_ADDRESS",
            "user_action": "SUBSCRIBE_NOW",
            "payment_method": {
            "payer_selected": "PAYPAL",
            "payee_preferred": "IMMEDIATE_PAYMENT_REQUIRED"
            },
            "return_url": "https://webdesignpreviews.com/custom/storyshare/public/api/create-subscription-success?user_id='.$userId.'",
            "cancel_url": "https://webdesignpreviews.com/custom/storyshare/public/api/create-subscription-failed"
        }
        }',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '. $request->paypalToken,
            'Content-Type: application/json',
            'Cookie: cookie_prefs=P%3D1%2CF%3D1%2Ctype%3Dimplicit; enforce_policy=ccpa; ts=vreXpYrS%3D1731431702%26vteXpYrS%3D1636762725%26vt%3D168db64b17dac120001b1c17ffff7109%26vr%3D168db64b17dac120001b1c17ffff7108; ts_c=vr%3D168db64b17dac120001b1c17ffff7108%26vt%3D168db64b17dac120001b1c17ffff7109'
        ),
        ));

        $response = curl_exec($curl);
        
        curl_close($curl);

        $response = json_decode($response, true);
        
        return response()->json([
            'success' => $response['status'],
            'links' => $response['links']
        ]);

    }

    public function createSubscriptionFailed()
    {
        return "createSubscriptionFailed";
    }
    
    public function createSubscriptionSuccess(Request $request)
    {
        $subscription = new Subscription;
        $subscription->user_id=$request->user_id; //getting user id from jwtAuth
        $subscription->paypal_subscription_id=$request->subscription_id; //getting subscription_id from paypal response
        $subscription->save();
        
        return redirect('subscription-success');
        /*return response()->json([
            'success' => true,
            'subscription' => $request->subscription_id
        ]);*/
    }
        
    public function subscriptionSuccess()
    {
        return response()->json([
            'success' => true
        ]);
    }
    
    public function cancelSubscription(Request $request)
    {   
        $paypalToken = $request->paypalToken;
        $userId = JWTAuth::user()->id;
        $subscription = Subscription::where('user_id', $userId)->get()->last();
        
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api-m.paypal.com/v1/billing/subscriptions/'.$subscription->paypal_subscription_id.'/cancel',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
          "reason": "Not satisfied with the service"
        }',
          CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.$paypalToken,
            'Content-Type: application/json',
            'Cookie: cookie_prefs=P%3D1%2CF%3D1%2Ctype%3Dimplicit; enforce_policy=ccpa; ts=vreXpYrS%3D1731431702%26vteXpYrS%3D1636762725%26vt%3D168db64b17dac120001b1c17ffff7109%26vr%3D168db64b17dac120001b1c17ffff7108; ts_c=vr%3D168db64b17dac120001b1c17ffff7108%26vt%3D168db64b17dac120001b1c17ffff7109'
          ),
        ));
        
        $response = curl_exec($curl);
        
        $http_status=false;

        // Check HTTP status code
        if (!curl_errno($curl)) {
          switch ($http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
            case 204:  # OK
          
              $subscription->is_active=0;
              $subscription->save();
                $http_status=true;
              break;

            default:
              $http_status=false;
          }
        }
        curl_close($curl);
        return response()->json([
            'success' => $http_status,
            'subscription_id' => $subscription->paypal_subscription_id
        ]);

    }

    public function checkSubscription()
    {
        $user = User::find(JWTAuth::user()->id);
        
        return response()->json([
                'success' => true,
                'isActive' => ($user->latest_subscription)?($user->latest_subscription->is_active==1?true:false):false
            ]);
    }
}
