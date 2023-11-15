<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 17-04-2023
 * Time: 12:00
 * Store, edit,list, delete the news and events
 */
namespace App\Http\Controllers\Version2;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\AcademicClassConfiguration;
use App\Models\CommunicationRecipients;
use App\Models\NewsEventsAttachments;
use App\Models\NewsEventAcceptStatus;
use App\Models\UserStudentsMapping;
use App\Models\UserGroupsMapping;
use App\Models\UserManagements;
use App\Models\UserCategories;
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


class V2APINewsEventsController extends Controller
{

    //View Main screen news and events 
    public function mainscreen_view_newsevents(Request $request)
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
                $student_id[] = $request->student_id;
            $class_config = UserStudents::whereIn('id',$student_id)->pluck('class_config')->first();
            $newsevents = $newsevents->where(function($query) use ($class_config){
                $query->where('visible_to','like','%,'.$class_config.',%')
                    ->orWhere('visible_to','all');
            })->where('status',1)->where('module_type',1);
        }
        $newsevents = $newsevents->orderBy('published_time','DESC')->get()->toArray();//fetch all the news data
        $latest = $olddata = [];
        // fetch common details from table
        $user_table_id = app('App\Http\Controllers\APILoginController')->get_user_table_id($user);

        $userall_id = UserAll::where(['user_table_id'=>$user_table_id->id,'user_role'=>$user->user_role])->pluck('id')->first();//get common id 

        $management_categories = array_column(UserCategories::select('id','category_name')->where('user_role',Config::get('app.Management_role'))->get()->toArray(),'category_name','id');

        $staff_categories = array_column(UserCategories::select('id','category_name')->where('user_role',Config::get('app.Staff_role'))->get()->toArray(),'category_name','id');

        // fetch all liked data
        $liked_news = NewsEventAcceptStatus::where('user_id',$userall_id)->where('accept_status',1)->pluck('news_event_id')->toArray();

        $total_like = [];
        $total_like = NewsEventAcceptStatus::select(DB::raw('COUNT(accept_status) as accept_status'),'news_event_id')->where('accept_status',1)->groupBy('news_event_id')->get()->toArray();
        if(!empty($total_like))
            $total_like = array_column($total_like,'accept_status','news_event_id');

        // $currentPage = LengthAwarePaginator::resolveCurrentPage(); // Get current page form url e.x. &page=1
        $currentPage = $request->page;
        $itemCollection = new Collection($newsevents); // Create a new Laravel collection from the array data
        $perPage = ($currentPage == 1)?11:10;
        // Slice the collection to get the items to display in current page
        // $sortedCollection = $itemCollection->sortByDesc('admission_no');
        $currentPageItems = $itemCollection->values()->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems= new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);

        $paginatedItems->setPath($request->url()); // set url path for generted links
        $paginatedItems->appends($request->page);

        $tempdata = $paginatedItems->toArray();
        $olddata['total'] = $tempdata['total'];
        $olddata['per_page'] = $tempdata['per_page'];
        $olddata['current_page'] = $tempdata['current_page'];
        $olddata['last_page'] = $tempdata['last_page'];
        $olddata['next_page_url'] = $tempdata['next_page_url'];
        $olddata['prev_page_url'] = $tempdata['prev_page_url'];
        $olddata['from'] = $tempdata['from'];
        $olddata['to'] = $tempdata['to'];

        $index =0;
        foreach ($tempdata['data'] as $key => $value) { //loop to format all the data in display formaat
            $news = $images = $addon_images = []; //empty declartion
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
            $userall_id = UserAll::select('user_table_id','user_role')->where('id',$value['created_by'])->get()->first();//get common id 
            $sender_details = $this->userDetails($userall_id);
            $user = $designation = '';
            if(!empty($sender_details) && $userall_id->user_role == Config::get('app.Management_role'))
            {
                $user = isset($management_categories[$sender_details['user_category']])?ucfirst($sender_details['first_name'])." ".$management_categories[$sender_details['user_category']]:ucfirst($sender_details['first_name']);
                $designation = 'Management';
            }
            else if(!empty($sender_details) && $userall_id->user_role == Config::get('app.Staff_role'))
            {
                $user = ucfirst($sender_details['first_name']);
                $designation = $staff_categories[$sender_details['user_category']];
            }
            else if(!empty($sender_details) && $userall_id->user_role == Config::get('app.Admin_role'))
            {
                $user = ucfirst($sender_details['first_name']);
                $designation = 'Admin';
            }
            else if(!empty($sender_details) && $userall_id->user_role == Config::get('app.Parent_role'))
            {
                $user = ucfirst($sender_details['first_name']);
                $designation = 'F/O Test';
            }
            // array formated to display news
            $news = ([
                'id'=>$value['id'],
                'user'=>$user,
                'designation'=>$designation,
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
            if($index == 0)
                $latest = $news; //latest news
            else
                $olddata['data'][] = $news; //old news
            $index++;
        }
        return response()->json(['latest'=>$latest,'old'=>$olddata]);
    }

    // view all the images in gallery tap
    public function view_all_images(Request $request)
    {
        $images = [];
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
                $student_id[] = $request->student_id;
            $class_config = UserStudents::whereIn('id',$student_id)->pluck('class_config')->first();
            $newsevents = $newsevents->where(function($query) use ($class_config){
                $query->where('visible_to','like','%,'.$class_config.',%')
                    ->orWhere('visible_to','all');
            })->where('status',1);
        }

        $newsevents = $newsevents->orderBy('published_time','DESC')->get()->toArray();//fetch all the images data

        $management_categories = array_column(UserCategories::select('id','category_name')->where('user_role',Config::get('app.Management_role'))->get()->toArray(),'category_name','id');

        $staff_categories = array_column(UserCategories::select('id','category_name')->where('user_role',Config::get('app.Staff_role'))->get()->toArray(),'category_name','id');

        // $currentPage = LengthAwarePaginator::resolveCurrentPage(); // Get current page form url e.x. &page=1
        $currentPage = $request->page;
        $itemCollection = new Collection($newsevents); // Create a new Laravel collection from the array data
        $perPage = 6;
        // Slice the collection to get the items to display in current page
        // $sortedCollection = $itemCollection->sortByDesc('admission_no');
        $currentPageItems = $itemCollection->values()->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems= new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);

        $paginatedItems->setPath($request->url()); // set url path for generted links
        $paginatedItems->appends($request->page);

        $tempdata = $paginatedItems->toArray();
        $images['total'] = $tempdata['total'];
        $images['per_page'] = $tempdata['per_page'];
        $images['current_page'] = $tempdata['current_page'];
        $images['last_page'] = $tempdata['last_page'];
        $images['next_page_url'] = $tempdata['next_page_url'];
        $images['prev_page_url'] = $tempdata['prev_page_url'];
        $images['from'] = $tempdata['from'];
        $images['to'] = $tempdata['to'];

        foreach ($tempdata['data'] as $key => $value) { //loop to format all the data in display formaat
            $image_ids = explode(',', $value['images']);//fetch main images
            echo '<pre>';print_r($image_ids);
            print_r($value);exit;
            $images_list = NewsEventsAttachments::where(['news_events_id'=>$value['id']])->whereIn('id',$image_ids)->get()->toArray();//fetch path and images name details from table.

            $userall_id = UserAll::select('user_table_id','user_role')->where('id',$value['created_by'])->get()->first();//get common id 
            $sender_details = $this->userDetails($userall_id);
            $user = $designation = '';
            if(!empty($sender_details) && $userall_id->user_role == Config::get('app.Management_role'))
            {
                $user = isset($management_categories[$sender_details['user_category']])?ucfirst($sender_details['first_name'])." ".$management_categories[$sender_details['user_category']]:ucfirst($sender_details['first_name']);
                $designation = 'Management';
            }
            else if(!empty($sender_details) && $userall_id->user_role == Config::get('app.Staff_role'))
            {
                $user = ucfirst($sender_details['first_name']);
                $designation = $staff_categories[$sender_details['user_category']];
            }
            else if(!empty($sender_details) && $userall_id->user_role == Config::get('app.Admin_role'))
            {
                $user = ucfirst($sender_details['first_name']);
                $designation = 'Admin';
            }
            else if(!empty($sender_details) && $userall_id->user_role == Config::get('app.Parent_role'))
            {
                $user = ucfirst($sender_details['first_name']);
                $designation = 'F/O Test';
            }
            if(!empty($images_list))//check if empty
            {
                foreach ($images_list as $image_key => $image_value) {//form array 
                    $images['data'][]= ([
                        'id'=>$image_value['id'],
                        'news_events_id'=>$value['id'],
                        'image'=>$image_value['attachment_location'].'/'.$image_value['attachment_name'],
                        'user'=>$user,
                        'designation'=>$designation,
                        'datetime'=>($value['published_time'] !=null)?$value['published_time']:null,

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
                            'user'=>$user,
                            'designation'=>$designation,
                            'datetime'=>($value['published_time'] !=null)?$value['published_time']:null,

                        ]);

                    }
                }
            }
        }
        return response()->json($images);
    }

    //View Main screen news and events 
    public function mainscreen_view_allevents(Request $request)
    {
        // Check authenticate user.
        $user = auth()->user();
        $user->last_login = Carbon::now()->timezone('Asia/Kolkata');
        $user->save();
        $newsevents = NewsEvents::where(['published'=>'Y','status'=>1,'module_type'=>2]);

        if($user->user_role == Config::get('app.Parent_role'))
        {
            if($request->student_id =='')
            {
                $user_table_id = UserParents::where(['user_id'=>$user->user_id])->first();//fetch id from user all table to store notification triggered user
                $student_id = UserStudentsMapping::where(['parent'=>$user_table_id->id])->pluck('student')->toArray();
            }
            else
                $student_id[] = $request->student_id;
            $class_config = UserStudents::whereIn('id',$student_id)->pluck('class_config')->first();

            $newsevents = $newsevents->where(function($query) use ($class_config){
                $query->where('visible_to','like','%,'.$class_config.',%')
                    ->orWhere('visible_to','all');
            })->where('status',1)->where('module_type',2);

        }
        $old_events_list = $newsevents->where('event_date','<',date("Y-m-d",strtotime(Carbon::now()->timezone('Asia/Kolkata'))))->orderBy('event_date','DESC')->get()->toArray();//fetch all the news data

        $newevents = NewsEvents::where(['published'=>'Y','status'=>1,'module_type'=>2]);

        if($user->user_role == Config::get('app.Parent_role'))
        {
            if($request->student_id =='')
            {
                $user_table_id = UserParents::where(['user_id'=>$user->user_id])->first();//fetch id from user all table to store notification triggered user
                $student_id = UserStudentsMapping::where(['parent'=>$user_table_id->id])->pluck('student')->toArray();
            }
            else
                $student_id[] = $request->student_id;
            $class_config = UserStudents::whereIn('id',$student_id)->pluck('class_config')->first();

            $newevents = $newevents->where(function($query) use ($class_config){
                $query->where('visible_to','like','%,'.$class_config.',%')
                    ->orWhere('visible_to','all');
            })->where('status',1)->where('module_type',2);

        }
        $upcoming_events_list = $newevents->where('event_date','>=',date("Y-m-d",strtotime(Carbon::now()->timezone('Asia/Kolkata'))))->orderBy('event_date','DESC')->get()->toArray();//fetch all upcoming evnts data

        $management_categories = array_column(UserCategories::select('id','category_name')->where('user_role',Config::get('app.Management_role'))->get()->toArray(),'category_name','id');

        $staff_categories = array_column(UserCategories::select('id','category_name')->where('user_role',Config::get('app.Staff_role'))->get()->toArray(),'category_name','id');

        $upcoming_events = $completed_events = [];
        $url = $request->url();
        $page = $request->page;

        $result = $this->eventsdata($upcoming_events_list,$old_events_list,$management_categories,$staff_categories,'new',$url,$page);
        
        $old_events_list = $result['old_events_list'];
        unset($result['old_events_list']);

        if(!empty($result))
            $upcoming_events = $result;

        $completed_events = $this->eventsdata($upcoming_events_list,$old_events_list,$management_categories,$staff_categories,'old',$url,$page);

        return response()->json(['upcoming_events'=>$upcoming_events,'completed_events'=>$completed_events]);
    }

    public function userDetails($userall_id)
    {
        $user_details =[];
        if(!empty($userall_id))
        {
            if($userall_id->user_role == Config::get('app.Management_role'))
                $user_details =UserManagements::where(['id'=>$userall_id->user_table_id])->first();
            else if($userall_id->user_role == Config::get('app.Staff_role'))
                $user_details =UserStaffs::where(['id'=>$userall_id->user_table_id])->first();
            else if($userall_id->user_role == Config::get('app.Parent_role'))
                $user_details =UserParents::where(['id'=>$userall_id->user_table_id])->first();
            else if($userall_id->user_role == Config::get('app.Admin_role'))
                $user_details =UserAdmin::where(['id'=>$userall_id->user_table_id])->first();
        }
        if(!empty($user_details))
            return $user_details->toArray();
        else
            return $user_details;

    }

    public function eventsdata($upcoming_events_list,$old_events_list,$management_categories,$staff_categories,$type,$url,$page)
    {
        // Check authenticate user.
        $user = auth()->user();
        // $currentPage = LengthAwarePaginator::resolveCurrentPage(); // Get current page form url e.x. &page=1
        $currentPage = $page;
        $list = ($type == 'new')?$upcoming_events_list:$old_events_list;
        $itemCollection = new Collection($list); // Create a new Laravel collection from the array data
        $perPage = 20;
        // Slice the collection to get the items to display in current page
        // $sortedCollection = $itemCollection->sortByDesc('admission_no');
        $currentPageItems = $itemCollection->values()->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems= new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);

        $paginatedItems->setPath($url); // set url path for generted links
        $paginatedItems->appends($page);

        $tempdata = $paginatedItems->toArray();
        $events_data['total'] = $tempdata['total'];
        $events_data['per_page'] = $tempdata['per_page'];
        $events_data['current_page'] = $tempdata['current_page'];
        $events_data['last_page'] = $tempdata['last_page'];
        $events_data['next_page_url'] = $tempdata['next_page_url'];
        $events_data['prev_page_url'] = $tempdata['prev_page_url'];
        $events_data['from'] = $tempdata['from'];
        $events_data['to'] = $tempdata['to'];
        $events_data['data'] =[];
        if($type == 'new')
            $events_data['old_events_list'] = [];
        
        if($currentPage > 0){
            foreach ($tempdata['data'] as $key => $value) { //loop to format all the data in display formaat
                date_default_timezone_set("Asia/Calcutta"); 
                $proceed = 1;

                if(strtotime(date('Y-m-d')) == strtotime($value['event_date']) && (strtotime(date('H:i:s')) > strtotime($value['event_time'])) && $type == 'new')
                {
                    array_unshift($old_events_list,$value);
                    $proceed = 0;
                }
                
                if($proceed == 1)
                {
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

                    $userall_id = UserAll::select('user_table_id','user_role')->where('id',$value['created_by'])->get()->first();//get common id 
                    $sender_details = $this->userDetails($userall_id);
                    $userdata = $designation = '';
                    if(!empty($sender_details) && $userall_id->user_role == Config::get('app.Management_role'))
                    {
                        $userdata = isset($management_categories[$sender_details['user_category']])?ucfirst($sender_details['first_name'])." ".$management_categories[$sender_details['user_category']]:ucfirst($sender_details['first_name']);
                        $designation = 'Management';
                    }
                    else if(!empty($sender_details) && $userall_id->user_role == Config::get('app.Staff_role'))
                    {
                        $userdata = ucfirst($sender_details['first_name']);
                        $designation = $staff_categories[$sender_details['user_category']];
                    }
                    else if(!empty($sender_details) && $userall_id->user_role == Config::get('app.Admin_role'))
                    {
                        $userdata = ucfirst($sender_details['first_name']);
                        $designation = 'Admin';
                    }
                    else if(!empty($sender_details) && $userall_id->user_role == Config::get('app.Parent_role'))
                    {
                        $userdata = ucfirst($sender_details['first_name']);
                        $designation = 'F/O Test';
                    }

                    // array formated to display news
                    $events_detail = ([
                        'id'=>$value['id'],
                        'user'=>$userdata,
                        'designation'=>$designation,
                        'datetime'=>($value['published_time'] !=null)?$value['published_time']:null,
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
                        $events_detail['accept_status'] = !empty($all_accept_status)?$all_accept_status:0;
                        $events_detail['accepted']=isset($count_result[1])?$count_result[1]:0;
                        $events_detail['declined']=isset($count_result[2])?$count_result[2]:0;

                    }
                    $events_data['data'][] = $events_detail; 
                }
                
            }
        }
        if($type == 'new')
            $events_data['old_events_list'] = $old_events_list; 
        
        return $events_data; 
    }
}