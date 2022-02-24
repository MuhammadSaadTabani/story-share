<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Post;
use App\Models\Like;
use DB;

class PostController extends Controller
{
    
    //Create Post Api
    public function createPost(Request $request)
    {
        $postData = $request->all();

        $user = JWTAuth::user()->id; //getting user id from jwtAuth
        
        $postData['user_id'] = $user;
        $postData['created_at'] = date('Y-m-d H:i:s');

        if($request->hasFile('video')){
            $fileName = time(). '-'. rand() . '.'.$request->video->extension();  
        
            $request->video->move(public_path('videos'), $fileName);
        
            $filePath = url('videos').'/'. $fileName;    

            $postData['video'] = $filePath;
        }
    
        $createPost = Post::create($postData);

        if($createPost){
            return response()->json([
                'success' => true,
                'message' => 'Post created successfully'
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Failed to create post'
            ]);
        }
    }
   
    //Edit/Update Post Api
    public function editPost(Request $request, $idPost)
    {
        $postData = $request->all();

        $user = JWTAuth::user()->id; //getting user id from jwtAuth
        
        $postData['user_id'] = $user;

        if($request->hasFile('video')){
            $fileName = time(). '-'. rand() . '.'.$request->video->extension();  
        
            $request->video->move(public_path('videos'), $fileName);
        
            $filePath = url('videos').'/'. $fileName;    

            $postData['video'] = $filePath;
        }


        $createPost = Post::where('id', $idPost)->update($postData);

        if($createPost){
            return response()->json([
                'success' => true,
                'message' => 'Post updated successfully'
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Failed to update post'
            ]);
        }
    }

    public function getAllPosts()
    {
        $getAllPosts = Post::where('isDeleted', '0')->orderBy('id', 'desc')->paginate(10);

        $arrayPostData = array();
        for($i = 0; $i < count($getAllPosts); $i++){
            
            $getUserLikedPost = DB::table('post_likes')->where(['post_id' => $getAllPosts[$i]->id, 'liked_by' => $getAllPosts[$i]->user_id])->get();
            $getTotalLikedPost = DB::table('post_likes')->where(['post_id' => $getAllPosts[$i]->id])->count();

            if(count($getUserLikedPost) > 0){
                $liked  = 1;
            }else{
                $liked  = 0;
            }
            $array = array(
                'id'                => $getAllPosts[$i]->id,
                'user_id'           => $getAllPosts[$i]->user_id,
                'UserProfileImage'  => $getAllPosts[$i]->user->image,
                'full_name'         => $getAllPosts[$i]->user->full_name,
                'video'             => $getAllPosts[$i]->video,
                'post_title'        => $getAllPosts[$i]->post_title,
                'post_description'  => $getAllPosts[$i]->post_description,
                'isLiked'           => $liked,
                'TotalLikes'        => $getTotalLikedPost,
                'type'              => $getAllPosts[$i]->type
            );
            array_push($arrayPostData, $array);
        }

        if(count($arrayPostData) > 0){
            return response()->json([
                'success' => true,
                'data' => $arrayPostData
            ]);
        }else{
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }
    }

    public function getPostDetails($idPost)
    {
        $getPostDetails = Post::where(['id'=> $idPost, 'isDeleted' => '0'])->first();

        if($getPostDetails != null){
            $getUserLikedPost = DB::table('post_likes')->where(['post_id' => $getPostDetails->id, 'liked_by' => $getPostDetails->user_id])->get();
            $getTotalLikedPost = DB::table('post_likes')->where(['post_id' => $getPostDetails->id])->count();
    
            if(count($getUserLikedPost) > 0){
                $liked  = 1;
            }else{
                $liked  = 0;
            }
            $postDetail = [
                'id'                => $getPostDetails->id,
                'user_id'           => $getPostDetails->user_id,
                'UserProfileImage'  => $getPostDetails->user->image,
                'full_name'         => $getPostDetails->user->full_name,
                'video'             => $getPostDetails->video,
                'post_title'        => $getPostDetails->post_title,
                'post_description'  => $getPostDetails->post_description,
                'isLiked'           => $liked,
                'TotalLikes'        => $getTotalLikedPost,
                'type'              => $getPostDetails->type
            ];
    
            if($getPostDetails != null){
                return response()->json([
                    'success' => true,
                    'data' => $postDetail
                ]);
            }else{
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }
        }else{
            return response()->json([
                'success' => false,
                'data' => []
            ]);
        }
    }

    public function deletePost($idPost)
    {
        $deletePost = Post::where('id', $idPost)->update(['isDeleted' => '1']);
        if($deletePost){
            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully'
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Failed to deleted post'
            ]);
        }
    }

    public function addComment(Request $request)
    {
        $postData = $request->all();

        $user = JWTAuth::user()->id; //getting user id from jwtAuth
        
        $postData['commented_by'] = $user;
        $postData['created_at'] = date('Y-m-d H:i:s');

        $createComment = Comment::create($postData);

        if($createComment){
            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully'
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment'
            ]);
        }
    }

    public function getCommentByPost($idPost)
    {
        $getCommentByPost = Comment::where('post_id', $idPost)->orderBy('id', 'desc')->paginate(10);
        $arrayCommentData = array();
        for($i= 0; $i < count($getCommentByPost); $i++)
        {
            $array = array(
                'id' => $getCommentByPost[$i]->id,
                'commented_by' => $getCommentByPost[$i]->commented_by,
                'UserProfileImage' => $getCommentByPost[$i]->user->image,
                'full_name' => $getCommentByPost[$i]->user->full_name,
                'comment' => $getCommentByPost[$i]->comment
            );
            array_push($arrayCommentData, $array);
        }

        if(count($getCommentByPost) > 0){
            return response()->json([
                'success' => true,
                'data' => $arrayCommentData
            ]);
        }else{
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }
    }

    public function addLike(Request $request)
    {
        $postData = $request->all();

        $user = JWTAuth::user()->id; //getting user id from jwtAuth
        $checkLike = Like::where('liked_by', $user)->first();
        if($checkLike != null){
            return response()->json([
                'success' => true,
                'message' => 'Already Liked'
            ]);
        }else{
            $postData['liked_by'] = $user;
            $postData['created_at'] = date('Y-m-d H:i:s');
            $addLike = Like::create($postData);

            if($addLike){
                return response()->json([
                    'success' => true,
                    'message' => 'Like successfully'
                ]);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to like'
                ]);
            }
        }
        
    }

    public function unLike(Request $request)
    {
        $postData = $request->all();
        $user = JWTAuth::user()->id; //getting user id from jwtAuth
        
        $deleteLike = Like::where(['post_id' => $postData['post_id'], 'liked_by' => $user])->delete();


        if($deleteLike){
            return response()->json([
                'success' => true,
                'message' => 'UnLike successfully'
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlike'
            ]);
        }
    }

    public function getPostByUserId($idUser)
    {
        $getPostByUserId = Post::where(['user_id'=> $idUser, 'isDeleted' => '0'])->orderBy('id', 'desc')->paginate(3);
        
        if(count($getPostByUserId) > 0)
        {
            $arrayPostsData = array();
            for($i=0; $i < count($getPostByUserId); $i++)
            {
                $getUserLikedPost = DB::table('post_likes')->where(['post_id' => $getPostByUserId[$i]->id, 'liked_by' => $getPostByUserId[$i]->user_id])->get();
                $getTotalLikedPost = DB::table('post_likes')->where(['post_id' => $getPostByUserId[$i]->id])->count();
                if(count($getUserLikedPost) > 0){
                    $liked  = 1;
                }else{
                    $liked  = 0;
                }
                $array = array(
                    'id'                => $getPostByUserId[$i]->id,
                    'user_id'           => $getPostByUserId[$i]->user_id,
                    'UserProfileImage'  => $getPostByUserId[$i]->user->image,
                    'full_name'         => $getPostByUserId[$i]->user->full_name,
                    'video'             => $getPostByUserId[$i]->video,
                    'post_title'        => $getPostByUserId[$i]->post_title,
                    'post_description'  => $getPostByUserId[$i]->post_description,
                    'isLiked'           => $liked,
                    'TotalLikes'        => $getTotalLikedPost,
                    'type'              => $getPostByUserId[$i]->type
                );
                array_push($arrayPostsData, $array);
            }
            if(count($arrayPostsData) > 0){
                return response()->json([
                    'success' => true,
                    'data' => $arrayPostsData
                ]);
            }else{
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }
        }else{
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }
        
    }
}
