<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\PaypalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PromptController;

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

Route::post('login', [UserController::class, 'authenticate']);
Route::post('register', [UserController::class, 'register']);
Route::post('forget-password-email', [UserController::class, 'ForgetPasswordEmail']);
Route::post('check-forget-password-code', [UserController::class, 'checkForgetPasswordCodeVerification']);
Route::post('update-forget-password', [UserController::class, 'updateForgetPassword']);
Route::get('get-privacy-policy', [UserController::class, 'getPrivacyPolicy']);
Route::get('get-terms-and-conditions', [UserController::class, 'getTermsAndConditions']);

Route::post('compress-image', [UserController::class, 'compressImage']);

Route::group(['middleware' => ['jwt.verify']], function() {
    Route::get('logout', [UserController::class, 'logout']);
    Route::get('get_user', [UserController::class, 'get_user']);
    Route::post('change-password', [UserController::class, 'changePassword']);
    Route::get('deactivate-account', [UserController::class, 'deactivateAccount']);
    
    Route::post('update-profile', [UserController::class, 'updateProfile']);
    Route::post('search-filter', [UserController::class, 'searchFilter']);

    //Post route start
    Route::post('create-post', [PostController::class, 'createPost']);
    Route::post('edit-post/{idPost}', [PostController::class, 'editPost']);
    Route::get('get-all-posts', [PostController::class, 'getAllPosts']);
    Route::get('get-post-details/{idPost}', [PostController::class, 'getPostDetails']);
    Route::get('get-posts-by-id/{idUser}', [PostController::class, 'getPostByUserId']);
    Route::get('delete-post/{idPost}', [PostController::class, 'deletePost']);
    //Post route end

    //Post Comment route start
    Route::post('add-comment', [PostController::class, 'addComment']);
    Route::get('get-comments/{idPost}', [PostController::class, 'getCommentByPost']);
    Route::post('add-like', [PostController::class, 'addLike']);
    Route::post('unlike', [PostController::class, 'unLike']);
    //Post Comment route end
    
    //Event route start
    Route::post('create-event', [EventController::class, 'createEvent']);
    Route::get('get-all-events/{idUser}', [EventController::class, 'getAllEvent']);
    Route::post('add-event-videos', [EventController::class, 'addEventVideos']);
    Route::get('delete-event/{idEvent}', [EventController::class, 'deleteEvent']);
    Route::get('delete-event-video/{idEventVideo}', [EventController::class, 'deleteEventVideo']);
    //Event route end

    //Prompt route start
    Route::post('create-prompt', [PromptController::class, 'createPrompt']);
    Route::get('get-all-prompts', [PromptController::class, 'getAllPrompts']);
    Route::post('edit-prompt/{idPrompt}', [PromptController::class, 'editPrompt']);
    Route::get('delete-prompt/{idPrompt}', [PromptController::class, 'deletePrompt']);
    //Prompt route end

    //Paypal route start
    Route::post('create-subscription', [PaypalController::class, 'createSubscription']);
    //Paypal route end
    
    Route::post('cancel-subscription', [PaypalController::class, 'cancelSubscription']);
    Route::get('check-subscription', [PaypalController::class, 'checkSubscription']);
    
});

Route::get('create-subscription-failed', [PaypalController::class, 'createSubscriptionFailed']);
Route::get('create-subscription-success', [PaypalController::class, 'createSubscriptionSuccess']);
Route::get('subscription-success', [PaypalController::class, 'subscriptionSuccess']);


// // Route::post('update-user-profile/{id}', [UserController::class, 'updateUserProfile']);
// // Route::post('email-registration', [UserController::class, 'emailRegistration']);
// Route::post('check-code-email', [UserController::class, 'checkCodeEmailVerification']);

