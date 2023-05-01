<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 17-04-2023
 * Time: 12:00
 * Store, edit,list, delete the news and events
 */
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\NewsEventsAttachments;
use App\Models\EventAcceptStatus;
use App\Models\UserManagements;
use App\Models\SchoolProfile;
use Illuminate\Http\Request;
use App\Models\NewsEvents;
use App\Models\UserAdmin;
use App\Models\Appusers;
use App\Models\UserAll;
use Carbon\Carbon;
use Validator;
use Config;
use File;
use URL;
use DB;


class APINewsEventsController extends Controller
{
	// Store or update news and events
	public function store_news_events(Request $request)
	{
		// Check authenticate user.
		$user = auth()->user();

		// get the common id to insert
		if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Management_role'))//check role and get current user id
            $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->pluck('id')->first();

        $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();//get common id 

		// Add rules to the login form
        $validator = Validator::make($request->all(), [
        	'title'=>'required',
            'description' => 'required_without_all:images',
            'images' => 'required_without_all:description',
        ]);
        // Validate login form
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $newsevents_id = '';
        $newsevents_id = $request->newsevents_id;
        $newsevents = ([
        	'title'=>$request->title,
        	'module_type'=>$request->module_type,//1-news,2-events
        	'image_type'=>$request->image_type, //1-single ,2-multiple
        	'description'=>$request->description,
        	'addon_description'=>(!empty($request->addon_description))?serialize($request->addon_description):null,
        	'addon_image_type'=>(!empty($request->addon_image_type))?serialize($request->addon_image_type):null,
        	'attachments'=>'N',
        	'status'=>1,//1-active,2-deactive,3-delete
        	'published'=>'Y',
        	'published_time'=>Carbon::now()->timezone('Asia/Kolkata'),
            'news_events_category'=>$request->news_events_category,
        ]);
        if(isset($request->important))
            $newsevents['important']=$request->important;
        if(isset($request->event_date))
            $newsevents['event_date']=$request->event_date;
        if(isset($request->event_time))
            $newsevents['event_time']=$request->event_time;
        if($newsevents_id == '')
        {
        	$newsevents['created_by']=$userall_id;
		    $newsevents['created_time']=Carbon::now()->timezone('Asia/Kolkata');
        	$newsevents_id = NewsEvents::insertGetId($newsevents);//Store news
        }
        else
        {
        	$newsevents['updated_by']=$userall_id;
		    $newsevents['updated_time']=Carbon::now()->timezone('Asia/Kolkata');
        	NewsEvents::where('id',$newsevents_id)->update($newsevents);//update news
        }

       	/*Move images to upload folder and store it in attachment table*/
        $attachment_id = $addone_attachement_id = [];
        if(count($_FILES)>0)
        {
        	$schoolcode = $school_profile = SchoolProfile::where(['id'=>$user['school_profile_id']])->get()->first();//get school code from school profile
        	$path = public_path('uploads/'.$school_profile['school_code']);//

		    if(!File::isDirectory($path)){ //check path already exists
		        File::makeDirectory($path, 0777, true, true);
		    }
            // Insert attachment details in attachment table
            if($request->hasfile('images')) {
			    
                foreach($request->file('images') as $file) //loop to insert images
                {	
                	if($newsevents_id!='')//delete already existing images
                	{

                	}

                    $attachment = new NewsEventsAttachments;
                    $attachment->news_events_id = $newsevents_id;
                    $name = explode('.',$file->getClientOriginalName());
                    $filename = str_replace(' ', '_', $name[0]);
                    $names = $filename.time().'.'.$name[1];
                    $file->move(public_path().'/uploads/'.$school_profile['school_code'], $names);  
                    $attachment->attachment_name = $names;
                    $attachment->attachment_type = 1;  //1-image
                    $attachment->attachment_location = url('/').'/uploads/'.$school_profile['school_code'];
                    $attachment->save();
                    $attachment_id[]= $attachment->id;
                }
            }

            if(!empty($request->addon_images)) {
            				    
                foreach($request->addon_images as $key=>$multiple_images) //loop to insert images
                {	
                	foreach ($multiple_images as $file_key => $file) {
                		if($newsevents_id!='')//delete already existing images
	                	{

	                	}

	                    $attachment = new NewsEventsAttachments;
	                    $attachment->news_events_id = $newsevents_id;
	                    $name = explode('.',$file->getClientOriginalName());
	                    $filename = str_replace(' ', '_', $name[0]);
	                    $names = $filename.time().'.'.$name[1];
	                    $file->move(public_path().'/uploads/'.$school_profile['school_code'], $names);  
	                    $attachment->attachment_name = $names;
	                    $attachment->attachment_type = 1;  //1-image
	                    $attachment->attachment_location = url('/').'/uploads/'.$school_profile['school_code'];
	                    $attachment->save();
	                    $addon_attachment_id[$key][]= $attachment->id;
                	}
                	
                }
            }

            if(!empty($attachment_id) || !empty($addon_attachment_id)) //check image exists or not
            {
            	$images_list = ([
            		'images'=>(!empty($attachment_id))?implode(',',$attachment_id):null,
            		'addon_images'=>(!empty($addon_attachment_id))?serialize($addon_attachment_id):null,
            		'attachments'=>'Y',

            	]);
            	NewsEvents::where('id',$newsevents_id)->update($images_list);//update newsandevents images
            }
        }
        
        return response()->json(['message'=>'Stored Successfully!...']);
	}

	//View Main screen news and events 
	public function mainscreen_view_newsevents()
	{
		// Check authenticate user.
		$user = auth()->user();
        $user->last_login = Carbon::now()->timezone('Asia/Kolkata');
        $user->save();
		$newsevents = NewsEvents::where(['published'=>'Y','status'=>1,'module_type'=>1])->orderBy('published_time','DESC')->get()->toArray();//fetch all the news data
        $latest = $olddata = [];
		foreach ($newsevents as $key => $value) { //loop to format all the data in display formaat
            $data = $images = $addon_images = []; //empty declartion
            $image_ids = explode(',', $value['images']);//fetch main images
            $images_list = NewsEventsAttachments::where(['news_events_id'=>$value['id']])->whereIn('id',$image_ids)->get()->toArray();//fetch path and images name details from table.
            if(!empty($images_list))//check if empty
            {
                foreach ($images_list as $image_key => $image_value) {//form array 
                    $images[]= $image_value['attachment_location'].'/'.$image_value['attachment_name'];
                }
            }

            // fetch if addon images added
            $addonimage_ids = unserialize($value['addon_images']); //fetch addon images
            if(!empty($addonimage_ids))//check empty
            {
                foreach ($addonimage_ids as $addon_key => $addon_value) {//loop to get all the images in multi-diemensional array format
                    $addonimages_list = NewsEventsAttachments::where(['news_events_id'=>$value['id']])->whereIn('id',$addon_value)->get()->toArray();
                    foreach ($addonimages_list as $addonimage_key => $addonimage_value) {
                        $addon_images[$addonimage_key][]= $addonimage_value['attachment_location'].'/'.$addonimage_value['attachment_name'];
                    }
                }
            }
            // array formated to display news
            $data = ([
                'id'=>$value['id'],
                'news_events_category'=>$value['news_events_category'],
                'datetime'=>($value['published_time'] !=null)?$value['published_time']:null,
                'title'=>$value['title'],
                'images'=>$images,
                'description'=>$value['description'],
                'important'=>($value['important'] == 'N')?'no':'yes',
                'addon_images'=>$addon_images,
                'addon_description'=>($value['addon_description']!='')?unserialize($value['addon_description']):null,
            ]);
            if($key == 0)
                $latest = $data; //latest news
            else
                $olddata[] = $data; //old news

		}

        return response()->json(['latest'=>$latest,'old'=>$olddata]);
	}

    // view all the images in gallery tap
    public function view_all_images()
    {
        // Check authenticate user.
        $user = auth()->user();
        $user->last_login = Carbon::now()->timezone('Asia/Kolkata');
        $user->save();
        $newsevents = NewsEvents::where(['published'=>'Y','status'=>1,'attachments'=>'Y'])->orderBy('published_time','DESC')->get()->toArray();//fetch all the images data

        foreach ($newsevents as $key => $value) { //loop to format all the data in display formaat
            $image_ids = explode(',', $value['images']);//fetch main images
            $images_list = NewsEventsAttachments::where(['news_events_id'=>$value['id']])->whereIn('id',$image_ids)->get()->toArray();//fetch path and images name details from table.
            if(!empty($images_list))//check if empty
            {
                foreach ($images_list as $image_key => $image_value) {//form array 
                    $images[]= ([
                        'id'=>$image_value['id'],
                        'news_events_id'=>$value['id'],
                        'image'=>$image_value['attachment_location'].'/'.$image_value['attachment_name'],
                    ]);
                }
            }

            // fetch if addon images added
            $addonimage_ids = unserialize($value['addon_images']); //fetch addon images
            if(!empty($addonimage_ids))//check empty
            {
                foreach ($addonimage_ids as $addon_key => $addon_value) {//loop to get all the images in multi-diemensional array format
                    $addonimages_list = NewsEventsAttachments::where(['news_events_id'=>$value['id']])->whereIn('id',$addon_value)->get()->toArray();
                    foreach ($addonimages_list as $addonimage_key => $addonimage_value) {
                        $images[]= ([
                            'id'=>$addonimage_value['id'],
                            'news_events_id'=>$value['id'],
                            'image'=>$addonimage_value['attachment_location'].'/'.$addonimage_value['attachment_name'],
                        ]);

                    }
                }
            }
        }
        return response()->json($images);
    }

    //news and event publish option
    public function publish_news_events(Request $request)
    {
        // Check authenticate user.
        $user = auth()->user();

        if($request->news_events_id!='')//check news and events was not empty
            $newsevents = NewsEvents::where(['status'=>1,'id'=>$request->news_events_id])->update(['published'=>'Y',
            'published_time'=>Carbon::now()->timezone('Asia/Kolkata')]);//publish news and events
        
        return response()->json(['message'=>'Published Successfully!...']);
    }

    //news and event delete option
    public function delete_news_events(Request $request)
    {
        // Check authenticate user.
        $user = auth()->user();

        if($request->news_events_id!='')//check news and events was not empty
            $newsevents = NewsEvents::where(['id'=>$request->news_events_id])->update(['status'=>3]);//delete news and events
        
        return response()->json(['message'=>'Deleted Successfully!...']);
    }

    // View individual News or events 
    public function view_individual_news_events(Request $request)
    {
        // Check authenticate user.
        $user = auth()->user();

        $newsevents = NewsEvents::where(['published'=>'Y','status'=>1,'module_type'=>1,'attachments'=>'Y','id'=>$request->news_events_id])->get()->first();//fetch all the images data

        $data = $images = $addon_images = []; //empty declartion
        $image_ids = explode(',', $newsevents->images);//fetch main images
        $images_list = NewsEventsAttachments::where(['news_events_id'=>$newsevents->id])->whereIn('id',$image_ids)->get()->toArray();//fetch path and images name details from table.
        if(!empty($images_list))//check if empty
        {
            foreach ($images_list as $image_key => $image_value) {//form array 
                $images[]= $image_value['attachment_location'].''.$image_value['attachment_name'];
            }
        }

        // fetch if addon images added
        $addonimage_ids = unserialize($newsevents->addon_images); //fetch addon images
        if(!empty($addonimage_ids))//check empty
        {
            foreach ($addonimage_ids as $addon_key => $addon_value) {//loop to get all the images in multi-diemensional array format
                $addonimages_list = NewsEventsAttachments::where(['news_events_id'=>$newsevents->id])->whereIn('id',$addon_value)->get()->toArray();
                foreach ($addonimages_list as $addonimage_key => $addonimage_value) {
                    $addon_images[$addonimage_key][]= $addonimage_value['attachment_location'].''.$addonimage_value['attachment_name'];
                }
            }
        }

        // array formated to display news
        $data = ([
            'id'=>$newsevents->id,
            'news_events_category'=>$newsevents->news_events_category,
            'datetime'=>($newsevents->published_time !=null)?$newsevents->published_time:null,
            'title'=>$newsevents->title,
            'images'=>$images,
            'description'=>$newsevents->description,
            'important'=>($newsevents->important == 'N')?'no':'yes',
            'addon_images'=>$addon_images,
            'addon_description'=>($newsevents->addon_description!='')?unserialize($newsevents->addon_description):null,
        ]);

        return response()->json($data);
    }

    //View Main screen news and events 
    public function mainscreen_view_allevents()
    {
        // Check authenticate user.
        $user = auth()->user();
        $user->last_login = Carbon::now()->timezone('Asia/Kolkata');
        $user->save();
        $newsevents = NewsEvents::where(['published'=>'Y','status'=>1,'module_type'=>2])->orderBy('event_date','DESC')->get()->toArray();//fetch all the news data
        $upcoming_events = $completed_events = [];
        foreach ($newsevents as $key => $value) { //loop to format all the data in display formaat
            $data = $images = []; //empty declartion
            $image_ids = explode(',', $value['images']);//fetch main images
            $images_list = NewsEventsAttachments::where(['news_events_id'=>$value['id']])->whereIn('id',$image_ids)->get()->toArray();//fetch path and images name details from table.
            if(!empty($images_list))//check if empty
            {
                foreach ($images_list as $image_key => $image_value) {//form array 
                    $images[]= $image_value['attachment_location'].'/'.$image_value['attachment_name'];
                }
            }

            $count_result = EventAcceptStatus::select(DB::raw('count(accept_status) as count'))->where('event_id',$value['id'])->groupBy('accept_status')->get()->toArray();

            // array formated to display news
            $data = ([
                'id'=>$value['id'],
                'news_events_category'=>$value['news_events_category'],
                'event_date'=>($value['event_date'] !=null)?$value['event_date']:null,
                'event_time'=>($value['event_time'] !=null)?$value['event_time']:null,
                'title'=>$value['title'],
                'images'=>$images,
                'description'=>$value['description'],
                'important'=>($value['important'] == 'N')?'no':'yes',
                'accepted' =>0,
                'declined'=>0,
            ]);
            if(!empty($count_result))
            {
                $data['accepted']=$count_result[0]['count'];
                $data['declined']=$count_result[1]['count'];

            }
            date_default_timezone_set("Asia/Calcutta");
            if(strtotime(date('Y-m-d')) <= strtotime($value['event_date']))
            {
                if(strtotime(date('Y-m-d')) == strtotime($value['event_date']) && (strtotime(date('H:i:s')) <= strtotime($value['event_time'])))
                    $upcoming_events[] = $data; //latest news
                else if(strtotime(date('Y-m-d')) < strtotime($value['event_date']) )
                    $upcoming_events[] = $data; //latest news
                else
                    $completed_events[] = $data; //old news
            }
            else
                $completed_events[] = $data; //old news

        }

        return response()->json(['upcoming_events'=>$upcoming_events,'completed_events'=>$completed_events]);
    }

    // attended and declined api
    public function event_accept_decline(Request $request)
    {
        // Check authenticate user.
        $user = auth()->user();

        // get the common id to insert
        if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Management_role'))//check role and get current user id
            $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Staff_role'))//check role and get current user id
            $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Parent_role'))//check role and get current user id
            $user_table_id = UserParents::where(['user_id'=>$user->user_id])->pluck('id')->first();

        $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();//get common id 

        $data = ([
            'user_id'=>$userall_id,
            'event_id'=>$request->event_id,
            'accept_status'=>$request->accept_status //1-accept,2-decline
        ]);

        EventAcceptStatus::insert($data);//insert details in db

        return response()->json(['message'=>'Updated...']);
    }
}