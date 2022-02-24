<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class PromptController extends Controller
{
    public function createPrompt(Request $request)
    {
        $postData = $request->all();
        $user = JWTAuth::user()->id; //getting user id from jwtAuth
        
        $postData['user_id'] = $user;
        $postData['created_at'] = date('Y-m-d H:i:s');
    
           
        $createPrompt = Prompt::create($postData);
        if($createPrompt){
            return response()->json([
                'success' => true,
                'message' => 'Prompt created successfully'
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Failed to create prompt'
            ]);
        }
    }

    public function getAllPrompts()
    {
        $getAllPrompts = Prompt::orderBy('id', 'desc')->get();

        if(count($getAllPrompts) > 0){           
            return response()->json([
                'success' => true,
                'data' => $getAllPrompts
            ]);
        }else{
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }
    }
    
    public function editPrompt(Request $request, $idPrompt)
    {
        $postData = $request->all();

        $updatePrompt = Prompt::where('id', $idPrompt)->update($postData);

        if($updatePrompt){

            return response()->json([
                'success' => true,
                'message' => 'Prompt updated successfully'
            ]);
        }else{
            return response()->json([
                'success' => true,
                'message' => 'Failed to update prompt'
            ]);
        }
    }

    public function deletePrompt($idPrompt)
    {
        $deletePrompt = Prompt::where('id', $idPrompt)->delete();
        if($deletePrompt)
        {
            return response()->json([
                'success' => true,
                'message' => 'Prompt deleted successfully'
            ]);
        }else{
            return response()->json([
                'success' => true,
                'message' => 'Failed to delete prompt'
            ]);
        }
    }
}
