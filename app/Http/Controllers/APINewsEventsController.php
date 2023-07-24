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
use App\Models\AcademicClassConfiguration;
use App\Models\CommunicationRecipients;
use App\Models\NewsEventsAttachments;
use App\Models\NewsEventAcceptStatus;
use App\Models\UserStudentsMapping;
use App\Models\UserGroupsMapping;
use App\Models\UserManagements;
use App\Models\SchoolProfile;
use Illuminate\Http\Request;
use App\Models\UserStudents;
use App\Models\UserParents;
use App\Models\NewsEvents;
use App\Models\UserStaffs;
use App\Models\UserGroups;
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
            'visible_to'=>!empty($request->visible_to)?implode(',',$request->visible_to):'all',
        ]);
        if(isset($request->youtube_link))
            $newsevents['youtube_link']=$request->youtube_link;
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
        if(!empty($_FILES) && count($_FILES)>0)
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
        
        $group_ids = UserGroups::where('group_status',1);
        if(!empty($request->visible_to) && $request->visible_to !='all')
            $group_ids = $group_ids->whereIn('class_config',$request->visible_to);
        
        $group_ids = $group_ids->pluck('id')->toArray();
        if(!empty($group_ids))
        {
            $user_list =UserGroupsMapping::whereIn('group_id',$group_ids)->where('user_status',Config::get('app.Group_Active'))->get()->toArray();
            $this->insert_receipt_log($user_list,$newsevents_id,$user_table_id);
        }

        return response()->json(['message'=>'Stored Successfully!...']);
    }

    public function insert_receipt_log($user_list,$notification_id='',$user_table_id='')
    {
        // Get authorizated user details
        $user = auth()->user();

        NewsEvents::where('id',$notification_id)->update(['delivered_users'=>count($user_list)]);
        $existing_userids = $communication_details= $player_ids = [];
        $message = NewsEvents::where('id',$notification_id)->get()->first();
        // Insert communication message in notification log receipt tables(School DB)
        foreach ($user_list as $key => $value) {

            $unique_userid = UserAll::where('user_table_id',$value['user_table_id'])->where('user_role',$value['user_role'])->pluck('id')->first();

            if(!in_array($unique_userid, $existing_userids)) //check the duplicate user details is exists 
            {
                $player_id = Appusers::where('loginid',$unique_userid)->pluck('player_id')->first(); //get player id for the users
                $data[] = ([
                    'communication_id'=>$notification_id,
                    'communication_type'=>2,
                    'user_table_id'=>$value['user_table_id'],
                    'user_role'=>$value['user_role'],
                    'message_status'=>1,
                    'view_type'=>($value['user_table_id'] == $user_table_id && $value['user_role'] == $user->user_role)?1:2,
                    'actioned_time'=>Carbon::now()->timezone('Asia/Kolkata'),
                    'player_id'=>$player_id
                ]); //form array to store notification details

                if($player_id!='') //check player id is not empty
                {
                    $player_ids[$value['user_role']] =$player_id;
                    if($message['module_type'] == 1)
                        $chat_message = 'A new News is avaliable';
                    else
                        $chat_message = 'A new Event is avaliable';
                }
                array_push($existing_userids,$unique_userid);
            }
        }
        CommunicationRecipients::insert($data); //inserted into log

        $delivery_details = APIPushNotificationController::SendNotification($chat_message,$player_ids,$notification_id,'chat'); //trigger pushnotification function
    }

    //View Main screen news and events 
    public function mainscreen_view_newsevents()
    {
        // Check authenticate user.
        $user = auth()->user();
        $user->last_login = Carbon::now()->timezone('Asia/Kolkata');
        $user->save();
        $newsevents = NewsEvents::where(['published'=>'Y','status'=>1,'module_type'=>1]);
        if($user->user_role == Config::get('app.Parent_role'))
        {
            if($request->student_id =='')
            {                
                $user_table_id = UserParents::where(['user_id'=>$user->user_id])->first();//fetch id from user all table to store notification triggered user
                $student_id = UserStudentsMapping::where(['parent'=>$user_table_id->id])->pluck('student')->toArray();
            }
            else
                $student_id = $request->student_id;
            $class_config = UserStudents::whereIn('id',$student_id)->pluck('class_config')->first();
            $newsevents=$newsevents->where(['visible_to'=>'all','module_type'=>1])->orWhere('visible_to', 'like', '%' .$class_config. '%')->where('module_type',1);
        }
        $newsevents = $newsevents->orderBy('published_time','DESC')->get()->toArray();//fetch all the news data
        $latest = $olddata = [];
        // fetch common details from table
        $user_table_id = app('App\Http\Controllers\APILoginController')->get_user_table_id($user);

        $userall_id = UserAll::where(['user_table_id'=>$user_table_id->id,'user_role'=>$user->user_role])->pluck('id')->first();//get common id 

        // fetch all liked data
        $liked_news = NewsEventAcceptStatus::where('user_id',$userall_id)->where('accept_status',1)->pluck('news_event_id')->toArray();

        $total_like = [];
        $total_like = NewsEventAcceptStatus::select(DB::raw('COUNT(accept_status) as accept_status'),'news_event_id')->where('accept_status',1)->groupBy('news_event_id')->get()->toArray();
        if(!empty($total_like))
            $total_like = array_column($total_like,'accept_status','news_event_id');

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
            $visibility ='';
            if($value['visible_to']!='all' && $value['visible_to']!='')
            {
                $class_section_names = $class_sections = [];
                $class_sections = AcademicClassConfiguration::whereIn('id',explode(',',$value['visible_to']))->get();
                if(!empty($class_sections))
                {
                    foreach($class_sections as $class_sec_key => $class_sec_value)
                    {
                        $class_section_names[] = $class_sec_value->classsectionName();
                    }
                    if(!empty($class_section_names))
                        $visibility = implode(',',$class_section_names);   
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
                'visibility'=>$visibility,
                'important'=>($value['important'] == 'N')?'no':'yes',
                'addon_images'=>$addon_images,
                'addon_description'=>($value['addon_description']!='')?unserialize($value['addon_description']):null,
                'youtube_link'=>$value['youtube_link'],
                'like'=>in_array($value['id'],$liked_news),
                'total_like'=>isset($total_like[$value['id']])?$total_like[$value['id']]:0,
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
        $newsevents = NewsEvents::where(['published'=>'Y','status'=>1,'attachments'=>'Y']);
        if($user->user_role == Config::get('app.Parent_role'))
        {
            if($request->student_id =='')
            {
                $user_table_id = UserParents::where(['user_id'=>$user->user_id])->first();//fetch id from user all table to store notification triggered user
                $student_id = UserStudentsMapping::where(['parent'=>$user_table_id->id])->pluck('student')->toArray();
            }
            else
                $student_id = $request->student_id;
            $class_config = UserStudents::whereIn('id',$student_id)->pluck('class_config')->first();
            $newsevents=$newsevents->where('visible_to','all')->orWhere('visible_to', 'like', '%' .$class_config. '%');
        }

        $newsevents = $newsevents->orderBy('published_time','DESC')->get()->toArray();//fetch all the images data

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

        $newsevents = NewsEvents::where(['published'=>'Y','status'=>1,'attachments'=>'Y','id'=>$request->news_events_id])->get()->first();//fetch all the images data
        $user_table_id = app('App\Http\Controllers\APILoginController')->get_user_table_id($user);

        $userall_id = UserAll::where(['user_table_id'=>$user_table_id->id,'user_role'=>$user->user_role])->pluck('id')->first();//get common id 
        
        if(!empty($newsevents))
        {
            $data = $images = $addon_images = []; //empty declartion
            $image_ids = explode(',', $newsevents->images);//fetch main images
            $images_list = NewsEventsAttachments::where(['news_events_id'=>$newsevents->id])->whereIn('id',$image_ids)->get()->toArray();//fetch path and images name details from table.
            if(!empty($images_list))//check if empty
            {
                foreach ($images_list as $image_key => $image_value) {//form array 
                    $images[]= $image_value['attachment_location'].'/'.$image_value['attachment_name'];
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
            $total_like = '';
            if($newsevents->module_type == 1)
            {
                // fetch all liked data
                $liked_news = NewsEventAcceptStatus::where('user_id',$userall_id)->where('accept_status',1)->where('news_event_id',$newsevents->id)->first();

                $total_like = NewsEventAcceptStatus::select(DB::raw('COUNT(accept_status) as accept_status'),'news_event_id')->where('accept_status',1)->where('news_event_id',$newsevents->id)->get()->first();
            }
            // array formated to display news
            $data = ([
                'id'=>$newsevents->id,
                'news_events_category'=>$newsevents->news_events_category,
                'datetime'=>($newsevents->published_time !=null)?$newsevents->published_time:null,
                'title'=>$newsevents->title,
                'images'=>$images,
                'description'=>$newsevents->description,
                'youtube_link'=>$newsevents->youtube_link,
                'important'=>($newsevents->important == 'N')?'no':'yes',
               
            ]);
            if($newsevents->module_type == 1)
            {
                $data['addon_images']=$addon_images;
                $data['addon_description']=($newsevents->addon_description!='')?unserialize($newsevents->addon_description):null;
                $data['like']=(!empty($liked_news))?true:false;
                $data['total_like']=(!empty($total_like))?$total_like->accept_status:0;
            }
            else
            {
                $data['accept_status']=0;
                $count_result = NewsEventAcceptStatus::select(DB::raw('count(accept_status) as count'),'accept_status')->where('news_event_id',$newsevents->id)->groupBy('accept_status')->get()->toArray();
                if(!empty($count_result))
                    $count_result = array_column($count_result,'count','accept_status');
                else
                    $count_result = [];
                $user_data = app('App\Http\Controllers\APILoginController')->get_user_table_id($user);
                $userall_id = UserAll::where(['user_table_id'=>$user_data->id,'user_role'=>$user->user_role])->pluck('id')->first();
                $all_accept_status = NewsEventAcceptStatus::where('news_event_id',$newsevents->id)->where('user_id',$userall_id)->pluck('accept_status')->first();
                $data['event_date']=($newsevents->event_date !=null)?$newsevents->event_date:null;
                $data['event_time']=($newsevents->event_time !=null)?$newsevents->event_time:null;
                $data['accept_status'] = !empty($all_accept_status)?$all_accept_status:0;
                $data['accepted']=isset($count_result[1])?$count_result[1]:0;
                $data['declined']=isset($count_result[2])?$count_result[2]:0;
            }

            return response()->json($data);
        }
        else
            return response()->json(['status'=>false,'message'=>'No News or Events.']);
    }


    //View Main screen news and events 
    public function mainscreen_view_allevents()
    {
        // Check authenticate user.
        $user = auth()->user();
        $user->last_login = Carbon::now()->timezone('Asia/Kolkata');
        $user->save();
        $newsevents = NewsEvents::where(['published'=>'Y','status'=>1,'module_type'=>2]);
        if($user->user_role == Config::get('app.Parent_role'))
        {
            if($request->student_id =='')
                $user_table_id = UserParents::where(['user_id'=>$user->user_id])->first();//fetch id from user all table to store notification triggered user
                $student_id = UserStudentsMapping::where(['parent'=>$user_table_id->id])->pluck('student')->toArray();
            }
            else
                $student_id = $request->student_id;
            $class_config = UserStudents::whereIn('id',$student_id)->pluck('class_config')->first();
            $newsevents=$newsevents->where('visible_to','all')->orWhere('visible_to', 'like', '%' .$class_config. '%')->where('module_type',2);
        }
        $newsevents=$newsevents->orderBy('event_date','DESC')->get()->toArray();//fetch all the news data
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

            $count_result = NewsEventAcceptStatus::select(DB::raw('count(accept_status) as count'),'accept_status')->where('news_event_id',$value['id'])->groupBy('accept_status')->get()->toArray();
            if(!empty($count_result))
                $count_result = array_column($count_result,'count','accept_status');
            else
                $count_result = [];

            $visibility ='';
            if($value['visible_to']!='all' && $value['visible_to']!='')
            {
                $class_section_names = $class_sections = [];
                $class_sections = AcademicClassConfiguration::whereIn('id',explode(',',$value['visible_to']))->get();
                if(!empty($class_sections))
                {
                    foreach($class_sections as $class_sec_key => $class_sec_value)
                    {
                        $class_section_names[] = $class_sec_value->classsectionName();
                    }
                    if(!empty($class_section_names))
                        $visibility = implode(',',$class_section_names);   
                }
            }

            // array formated to display news
            $data = ([
                'id'=>$value['id'],
                'news_events_category'=>$value['news_events_category'],
                'event_date'=>($value['event_date'] !=null)?$value['event_date']:null,
                'event_time'=>($value['event_time'] !=null)?$value['event_time']:null,
                'title'=>$value['title'],
                'images'=>$images,
                'description'=>$value['description'],
                'visibility'=>$visibility,
                'important'=>($value['important'] == 'N')?'no':'yes',
                'youtube_link'=>$value['youtube_link'],
                'accept_status'=>0,
                'accepted' =>0,
                'declined'=>0,
            ]);
            if(!empty($count_result))
            {
                $user_data = app('App\Http\Controllers\APILoginController')->get_user_table_id($user);
                $userall_id = UserAll::where(['user_table_id'=>$user_data->id,'user_role'=>$user->user_role])->pluck('id')->first();
                $all_accept_status = NewsEventAcceptStatus::where('news_event_id',$value['id'])->where('user_id',$userall_id)->pluck('accept_status')->first();
                $data['accept_status'] = !empty($all_accept_status)?$all_accept_status:0;
                $data['accepted']=isset($count_result[1])?$count_result[1]:0;
                $data['declined']=isset($count_result[2])?$count_result[2]:0;

            }
            date_default_timezone_set("Asia/Calcutta");
            if(strtotime(date('Y-m-d')) <= strtotime($value['event_date']))
            {
                if(strtotime(date('Y-m-d')) == strtotime($value['event_date']) && (strtotime(date('H:i:s')) <= strtotime($value['event_time'])))
                    array_unshift($upcoming_events,$data);//latest news
                else if(strtotime(date('Y-m-d')) < strtotime($value['event_date']) )
                    array_unshift($upcoming_events,$data);//latest news
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
        $status = NewsEventAcceptStatus::where('user_id',$userall_id)->where('news_event_id',$request->event_id)->get()->first();
        // store or update array 
        if(!empty($status))
        {
            $status->accept_status = $request->accept_status;
            $status->save();
        }
        else
        {
            $data = ([
                'user_id'=>$userall_id,
                'news_event_id'=>$request->event_id,
                'accept_status'=>$request->accept_status //1-accept,2-decline
            ]);

            NewsEventAcceptStatus::insert($data);//insert details in db
        }

        return response()->json(['message'=>'Updated...']);
    }

    // Store liked news in db
    public function store_liked_news(Request $request)
    {
        // Check authenticate user.
        $user = auth()->user();

        // fetch common details from table
        $user_table_id = app('App\Http\Controllers\APILoginController')->get_user_table_id($user);

        $userall_id = UserAll::where(['user_table_id'=>$user_table_id->id,'user_role'=>$user->user_role])->pluck('id')->first();//get common id 

        $status = NewsEventAcceptStatus::where('user_id',$userall_id)->where('news_event_id',$request->news_id)->get()->first();
        // store or update array 
        if(!empty($status))
        {
            $status->accept_status = $request->like_status;
            $status->save();
        }
        else
        {
            $data = ([
                'user_id'=>$userall_id,
                'news_event_id'=>$request->news_id,
                'accept_status'=>$request->like_status //1-accept,2-decline
            ]);

            NewsEventAcceptStatus::insert($data);//insert details in db
        }

        return response()->json(['message'=>'Updated...']);
    }
}