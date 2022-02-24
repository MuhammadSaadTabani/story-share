<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Event;
use App\Models\EventVideo;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class EventController extends Controller
{

    public function createEvent(Request $request)
    {
        $postData = $request->all();
        $user = JWTAuth::user()->id; //getting user id from jwtAuth
        $postData['created_at'] = date('Y-m-d H:i:s');
        $postData['user_id'] = $user;
           
        $createEvent = Event::create($postData);
        if($createEvent){

            if($request->hasFile('video')){
                for($i= 0; $i < count($_FILES['video']['name']); $i++)
                {
                    // var_dump($request->video[$i]);die;
                    $fileName = time(). '-'. rand() . '.'.$request->video[$i]->extension();  
                
                    $request->video[$i]->move(public_path('event_videos'), $fileName);
                
                    $filePath = url('event_videos').'/'. $fileName;    
        
                    $postDataEventVideo['video'] = $filePath;
                    $postDataEventVideo['created_at'] = date('Y-m-d H:i:s');
                    $postDataEventVideo['event_id'] = $createEvent->id;
                    $addEventVideos = EventVideo::create($postDataEventVideo);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Event created successfully'
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Failed to create event'
            ]);
        }
    }
    
    public function getAllEvent($idUser)
    {
        $getUser = User::where('id', $idUser)->get();
        $getAllEvents = Event::where('user_id', $idUser)->get();
        $getAllPosts = Post::where('user_id', $idUser)->get();
        $totalLikes = 0;
        for($j=0; $j < count($getAllPosts); $j++)
        {
            $getLikes = Like::where('post_id', $getAllPosts[$j])->count();
            $totalLikes += $getLikes;
        }
        $arrayEventsData = array();
        for($i = 0; $i < count($getAllEvents); $i++){
            
            $array = array(
                'id'                => $getAllEvents[$i]->id,
                'name'           => $getAllEvents[$i]->name,
                'videos'             => $getAllEvents[$i]->videos
            );
            array_push($arrayEventsData, $array);
        }
        
        if(count($arrayEventsData) > 0){

            //checking user image start
            if($getAllEvents[0]->user->image != ""){
                $image = $getAllEvents[0]->user->image;
            }else{
                $image = "";
            }
            //checking user image end

            //checking user cover image start
            if($getAllEvents[0]->user->cover_image != ""){
                $cover_image = $getAllEvents[0]->user->cover_image;
            }else{
                $cover_image = "";
            }
            //checking user cover image end

            //gathering user data start
            $arrayUser = array(
                'full_name' => $getAllEvents[0]->user->full_name,
                'image' => $image,
                'cover_image' => $cover_image
            );
            //gathering user data end

            return response()->json([
                'success'       => true,
                'TotalLikes'    => $totalLikes,
                'TotalPosts'    => count($getAllPosts),
                'user'          => $arrayUser, 
                'data'          => $arrayEventsData
            ]);
        }else{
            
            //checking user image start
            if($getUser[0]->image != ""){
                $image = $getUser[0]->image;
            }else{
                $image = "";
            }
            //checking user image end

            //checking user cover image start
            if($getUser[0]->cover_image != ""){
                $cover_image = $getUser[0]->cover_image;
            }else{
                $cover_image = "";
            }
            //checking user cover image end

            //gathering user data start
            $arrayUser = array(
                'full_name' => $getUser[0]->full_name,
                'image' => $image,
                'cover_image' => $cover_image
            );
            //gathering user data end
            
            return response()->json([
                'success'   => true,
                'TotalLikes'    => $totalLikes,
                'TotalPosts'    => count($getAllPosts),
                'user'          => $arrayUser, 
                'data'          => []
            ]);
        }
    }
    
    public function addEventVideos(Request $request)
    {
        $postDataEventVideo = $request->all();
        $postDataEventVideo['created_at'] = date('Y-m-d H:i:s');

        if($request->hasFile('video')){
            for($i= 0; $i < count($_FILES['video']['name']); $i++)
            {
                $fileName = time(). '-'. rand() . '.'.$request->video[$i]->extension();  
            
                $request->video[$i]->move(public_path('event_videos'), $fileName);
            
                $filePath = url('event_videos').'/'. $fileName;    
    
                $postDataEventVideo['video'] = $filePath;
                
                $addEventVideos = EventVideo::create($postDataEventVideo);
            }

            if($addEventVideos){
                return response()->json([
                    'success' => true,
                    'message' => 'Video added in event successfully'
                ]);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add videos in event'
                ]);
            }
        }
    }

    public function deleteEvent($idEvent)
    {
        $deleteEventVideos = EventVideo::where('event_id', $idEvent)->delete();

        $deleteEvent = Event::where('id', $idEvent)->delete();
        if($deleteEvent)
        {
            return response()->json([
                'success' => true,
                'message' => 'Event and event videos deleted successfully'
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete event and event videos'
            ]);
        }
    }

    public function deleteEventVideo($idEventVideo)
    {
        $deleteEventVideos = EventVideo::where('id', $idEventVideo)->delete();

        if($deleteEventVideos)
        {
            return response()->json([
                'success' => true,
                'message' => 'Event videos deleted successfully'
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete event video'
            ]);
        }
    }

}
