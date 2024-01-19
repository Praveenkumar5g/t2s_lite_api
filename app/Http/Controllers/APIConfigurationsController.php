<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 02-01-2023
 * Time: 05:40
 * Import class,sections ,staffs,managements ,students and parents details 
 */
namespace App\Http\Controllers;
 
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Excel as ExcelExcel;
use App\Models\AcademicClassConfiguration;
use App\Exports\MapClassesSectionsExport;
use App\Imports\MapClassesSectionsImport;
use App\Models\CommunicationAttachments;
use App\Models\CommunicationDistribution;
use App\Models\AcademicSubjectsMapping;
use App\Models\CommunicationRecipients;
use App\Models\HomeworkParentStatus;
use App\Models\UserStudentsMapping;
use App\Models\SchoolAcademicYears;
use App\Exports\MapSubjectsExport;
use App\Imports\ManagementsImport;
use App\Imports\SubDivisionImport;
use App\Imports\MapSubjectsImport;
use App\Models\AcademicDivisions;
use App\Models\UserGroupsMapping;
use App\Imports\MapStaffsImport;
use App\Models\AcademicSections;
use App\Models\AcademicSubjects;
use App\Models\Smsvendordetails;
use App\Imports\SubjectsImport;
use App\Exports\SubjectsExport;
use App\Models\MapStaffsExport;
use App\Exports\StudentsExport;
use App\Models\AcademicClasses;
use App\Models\UserManagements;
use App\Imports\StudentsImport;
use App\Models\Attendance;
use App\Imports\SectionsImport;
use App\Exports\SectionsExport;
use App\Models\UserCategories;
use App\Models\Communications;
use App\Imports\ClassesImport;
use App\Exports\ClassesExport;
use App\Models\Configurations;
use App\Imports\StaffsImport;
use App\Exports\StaffsExport;
use App\Models\SchoolProfile;
use App\Models\Smstemplates;
use Illuminate\Http\Request;
use App\Models\UserStudents;
use App\Models\UserParents;
use App\Models\UserGroups;
use App\Imports\DOBImport;
use App\Models\SchoolUsers;
use App\Models\UserStaffs;
use App\Models\UserRoles;
use App\Models\UserAdmin;
use App\Models\Appusers;
use App\Models\UserAll;
use App\Models\Smslogs;
use Carbon\Carbon;
use Validator;
use Storage;
use Config;
use File;
use URL;
use DB;


class APIConfigurationsController extends Controller
{
	// Configuration page list
	public function configuration_list()
	{
		// Save last login in DB
        $userdata = auth()->user();

        // Fetch configuration details from DB for corresponding school
        $configurations = Configurations::where('school_profile_id',$userdata->school_profile_id)->first();
        
        // configuration details
        $configuration = ([
            'classes'=>[
            	'config'=>($configurations->classes==1)?true:false,
            	'excel'=>(file_exists(public_path(env('SAMPLE_CONFIG_URL').'Classes.xlsx'))?env('APP_URL').env('SAMPLE_CONFIG_URL').'Classes.xlsx':'')
            ],
            'sections'=>[
            	'config'=>($configurations->sections==1)?true:false,
            	'excel'=>(file_exists(public_path(env('SAMPLE_CONFIG_URL').'Sections.xlsx'))?env('APP_URL').env('SAMPLE_CONFIG_URL').'Sections.xlsx':'')
            ],
            'map_classes_sections'=>[
            	'config'=>($configurations->map_classes_sections==1)?true:false,
            	'excel'=>(file_exists(public_path(env('SAMPLE_CONFIG_URL').'MapClassesSections.xlsx'))?env('APP_URL').env('SAMPLE_CONFIG_URL').'MapClassesSections.xlsx':'')
            ],
            'subjects'=>[
            	'config'=>($configurations->subjects==1)?true:false,
            	'excel'=>(file_exists(public_path(env('SAMPLE_CONFIG_URL').'Subjects.xlsx'))?env('APP_URL').env('SAMPLE_CONFIG_URL').'Subjects.xlsx':''),
            ],
            'map_subjects'=>[
            	'config'=>($configurations->map_subjects==1)?true:false,
            	'excel'=>(file_exists(public_path(env('SAMPLE_CONFIG_URL').'MapSubjects.xlsx'))?env('APP_URL').env('SAMPLE_CONFIG_URL').'MapSubjects.xlsx':''),
            ],
            'staffs'=>[
            	'config'=>($configurations->staffs==1)?true:false,
            	'excel'=>(file_exists(public_path(env('SAMPLE_CONFIG_URL').'Staffs.xlsx'))?env('APP_URL').env('SAMPLE_CONFIG_URL').'Staffs.xlsx':''),
            ],
            // 'map_staffs'=>[
            // 	'config'=>($configurations->map_staffs==1)?true:false,
            // 	'excel'=>(file_exists(public_path('uploads/M.xlsx'))?env('APP_URL').'uploads/classes_sections.xlsx':''),
            // ],
            'management'=>[
            	'config'=>($configurations->management==1)?true:false,
            	'excel'=>(file_exists(public_path(env('SAMPLE_CONFIG_URL').'Management.xlsx'))?env('APP_URL').env('SAMPLE_CONFIG_URL').'Management.xlsx':''),
            ],
            'students'=>[
            	'config'=>($configurations->students==1)?true:false,
            	'excel'=>(file_exists(public_path(env('SAMPLE_CONFIG_URL').'Students.xlsx'))?env('APP_URL').env('SAMPLE_CONFIG_URL').'Students.xlsx':''),
            ],
            // 'map_students'=>[
            // 	'config'=>($configurations->map_students==1)?true:false,
            // 	'excel'=>(file_exists(public_path('uploads/classes_sections.xlsx'))?env('APP_URL').'uploads/classes_sections.xlsx':''),
            // ]
        ]);

        // return token 
        return response()->json(compact('configuration'));
	}

	// Fetch classes ,sections,subjects list
	public function get_classes_sections_subjects_list()
	{
		$classes = AcademicClasses::select('id','class_name')->get()->toArray();
		$sections = AcademicSections::select('id','section_name')->get()->toArray();
		$subjects = AcademicSubjects::select('id','subject_name')->get()->toArray();
		return response()->json(compact('classes','sections','subjects'));
	}

	// Fetch classes list
	public function get_edit_classes_list(Request $request)
	{
		$classes = AcademicClasses::select('id','class_name')->where('division_id',$request->division_id)->get()->toArray();
		return response()->json(compact('classes'));
	}

	// Fetch classes list
	public function get_edit_allsection_list(Request $request)//get all the sections with the pne single sub-division for edit in on-boarding
	{
		$sections = AcademicSections::select('id','section_name')->where('division_id',$request->division_id)->get()->toArray();
		return response()->json(compact('sections'));
	}

	// Fetch student list
	public function get_edit_student_list() //get all the student for edit in on-boarding
	{
		$student_list = [];//empty array declaration
		$parents = UserParents::select('id','first_name','mobile_number')->where('user_category',1)->get()->toArray();//get student list
		return response()->json($parents); //return student details 
	}

	//Staffs Category
	public function get_staff_category()
	{
		$categories = UserCategories::select('id','category_name')->where('user_role',2)->get()->toArray();
		return response()->json(compact('categories'));
	}

	//Staffs Category
	public function get_staff_category_class()
	{
		$categories = UserCategories::select('id','category_name')->where('user_role',2)->get()->toArray();
		$subjects = AcademicSubjects::select('id','subject_name')->get()->toArray();
		$classes = AcademicClasses::select('id','class_name')->get()->toArray();
		$sections = AcademicSections::select('id','section_name')->get()->toArray();
		return response()->json(compact('categories','subjects','classes','sections'));
	}

	//Staffs Category
	public function get_allsubjects_list(Request $request)
	{
		$subjects = AcademicSubjects::select('id','subject_name')->where('division_id',$request->division_id)->get()->toArray();
		return response()->json(compact('subjects'));
	}

	// get classes and sections for edit
	public function get_edit_classes_sections()
	{
		$classes = AcademicClasses::select('id','class_name')->get()->toArray();
		$sections = AcademicSections::select('id','section_name')->get()->toArray();
		return response()->json(compact('classes','sections'));
	}

	// 
	public function get_staff_details(Request $request)
	{
		$subjects = AcademicSubjectsMapping::where('subject',$request->subject_id)->where('class_config',$request->class_config)->pluck('staff')->toArray();
		$all_staffs = UserStaffs::where('specialized_in',$request->subject_id)->get()->toArray();
		$staffs = [];
		if(!empty($all_staffs))
		{
			foreach ($all_staffs as $key => $value) {
				$staffs[] = ([
					'staff_name'=>$value['first_name'],
					'staff_id'=>$value['id'],
					'is_checked'=>(!empty($subjects) && in_array($value['id'],$subjects))?true:false
				]);
			}
		}
		return response()->json($staffs);
	}

	// All users list
	public function all_staff_list(Request $request)
	{
		// Save last login in DB
        $user = auth()->user();
        $member_staff_list = [];
        $staff_list = UserStaffs::select('id','first_name','mobile_number','user_category','user_status','dob','doj','employee_no','department','profile_image','user_id','email_id');
        if(isset($request->search) && $request->search!='')
        {
        	$category = (strpos('teaching staff',strtolower($request->search)))?Config::get('app.Teaching_staff'):((strpos('non teaching staff',strtolower($request->search)))?Config::get('app.Non-Teaching_staff'):'');
        	$staff_list = $staff_list->where('first_name', 'like', '%' . $request->search . '%')->orWhere('mobile_number', 'like', '%' . $request->search . '%')->orWhere('dob', 'like', '%' . $request->search . '%')->orWhere('doj', 'like', '%' . $request->search . '%')->orWhere('employee_no', 'like', '%' . $request->search . '%')->orWhere('department', 'like', '%' . $request->search . '%');
        	if($category!='')
        		$staff_list = $staff_list->orWhere('user_category', 'like', '%' . $category . '%');
        }
        $staff_list = $staff_list->get()->toArray();

        // $currentPage = LengthAwarePaginator::resolveCurrentPage(); // Get current page form url e.x. &page=1
        $currentPage = $request->page;
        $itemCollection = new Collection($staff_list); // Create a new Laravel collection from the array data
        $perPage = 10;
        // Slice the collection to get the items to display in current page
        $sortedCollection = $itemCollection->sortBy('user_category');
        $currentPageItems = $sortedCollection->values()->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems= new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);

        $paginatedItems->setPath($request->url()); // set url path for generted links
        $paginatedItems->appends($request->page);

        $tempdata = $paginatedItems->toArray();
        $member_staff_list['total'] = $tempdata['total'];
        $member_staff_list['per_page'] = $tempdata['per_page'];
        $member_staff_list['current_page'] = $tempdata['current_page'];
        $member_staff_list['last_page'] = $tempdata['last_page'];
        $member_staff_list['next_page_url'] = $tempdata['next_page_url'];
        $member_staff_list['prev_page_url'] = $tempdata['prev_page_url'];
        $member_staff_list['from'] = $tempdata['from'];
        $member_staff_list['to'] = $tempdata['to'];
        $list = ($currentPage <= 0)?$staff_list:$tempdata['data'];
        	
        foreach ($list as $key => $value) {
        	$check_access = SchoolUsers::where('user_id',$value['user_id'])->where('user_role',Config::get('app.Staff_role'))->where('user_status',2)->pluck('id')->first(); //2- full deactivate

        	if($check_access == '')
        		$check_access = UserGroupsMapping::where('user_table_id',$value['id'])->where('user_role',Config::get('app.Staff_role'))->where('user_status',1)->pluck('id')->first();

    	 	$classessections = AcademicClassConfiguration::select('id','class_id','section_id','division_id','class_teacher')->where('class_teacher',$value['id'])->first();

    	 	$member_staff_list['data'][]=([ 
    	 		'id' => $value['id'],
        		'first_name' => $value['first_name'],
	        	'mobile_number' => $value['mobile_number'],
	        	'user_id' => $value['user_id'],
	        	'email_id' => $value['email_id'],
	        	'user_category' => ($value['user_category'] ==Config::get('app.Teaching_staff'))?'Teaching_staff':'Non_teaching_staff',
	        	'dob' => $value['dob'],
	            'doj' => $value['doj'],
	            'employee_no' => $value['employee_no'],
	            'department' => $value['department'],
	            'user_status' => ($check_access == '')?3:$value['user_status'], // 1- active,2-full deactive, 3-partical deactive;
	            'class' => (!empty($classessections))?$classessections->classsectionName():'',
	            'designation' => $value['user_category'],
	            'profile_image' => (isset($value['profile_image']))?$value['profile_image']:'',
	        ]);
        }
        // if($currentPage <= 0)
        // 	$member_staff_list = $member_staff_list['data'];

	    return response()->json($member_staff_list);
	}

	// All users list
	public function all_parent_list(Request $request)
	{
		// Save last login in DB
        $user = auth()->user();
        $member_parent_list = [];
        $parent_list = UserStudents::select('user_students.first_name as student_name','p.id','p.user_category','p.mobile_number','p.user_status','p.profile_image as parent_profile_image','user_students.profile_image as student_profile_image','user_students.id as student_id','user_students.dob as dob','user_students.admission_number as admission_number','user_students.class_config as class_config','p.first_name','p.email_id')->join('user_students_mapping as sm','sm.student','=','user_students.id')->join('user_parents as p','p.id','=','sm.parent');
        if(isset($request->search) && $request->search!='')
            $parent_list = $parent_list->where('p.first_name', 'like', '%' . $request->search . '%')->orWhere('p.mobile_number', 'like', '%' . $request->search . '%');
        	
        $parent_list =$parent_list->get()->toArray();

        // $currentPage = LengthAwarePaginator::resolveCurrentPage(); // Get current page form url e.x. &page=1
        $currentPage = $request->page;
        $itemCollection = new Collection($parent_list); // Create a new Laravel collection from the array data
        $perPage = 10;
        // Slice the collection to get the items to display in current page
        $sortedCollection = $itemCollection->sortBy('class_config');
        $currentPageItems = $sortedCollection->values()->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems= new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);

        $paginatedItems->setPath($request->url()); // set url path for generted links
        $paginatedItems->appends($request->page);
		
		$tempdata = $paginatedItems->toArray();
        $member_parent_list['total'] = $tempdata['total'];
        $member_parent_list['per_page'] = $tempdata['per_page'];
        $member_parent_list['current_page'] = $tempdata['current_page'];
        $member_parent_list['last_page'] = $tempdata['last_page'];
        $member_parent_list['next_page_url'] = $tempdata['next_page_url'];
        $member_parent_list['prev_page_url'] = $tempdata['prev_page_url'];
        $member_parent_list['from'] = $tempdata['from'];
        $member_parent_list['to'] = $tempdata['to'];

        $index = 0;
        $list = ($currentPage <= 0)?$parent_list:$tempdata['data'];
        	
        foreach ($list as $key => $value) {
        	// $student_id = UserStudentsMapping::where('parent',$value['id'])->pluck('student')->toArray();
            // $student_details = UserStudents::whereIn('id',$student_id)->first();
            // echo '<pre>';print_r($student_details);
            $parent_list_data[$index] = $value;
        	$user_category = (strtolower($value['user_category']) == 1)?'F/O':'M/O';
        	$parent_list_data[$index]['student_name'] = ($user_category.' '.((isset($value['student_name']))?$value['student_name']:''));
        	$classessections =[];
        	if(isset($value['class_config']))
        		$classessections = AcademicClassConfiguration::select('id','class_id','section_id','division_id','class_teacher')->where('id',$value['class_config'])->first();
        	$parent_list_data[$index]['class'] = (!empty($classessections))?$classessections->classsectionName():'';
        	$parent_list_data[$index]['class_teacher'] = (!empty($classessections))?UserStaffs::where('id',$classessections->class_teacher)->pluck('first_name')->first():'';
        	$index++;
        }
	    // if($currentPage <= 0)
        // 	$member_parent_list = $parent_list_data;
        // else
        // {
        	$key_values = array_column($parent_list_data, 'class_config'); 
            array_multisort($key_values, SORT_ASC, $parent_list_data);
            $member_parent_list['data'] = $parent_list_data;
        // }
        return response()->json($member_parent_list);
	}

	// Store Onesignal device details in DB
    public function onesignal_store_device_details(Request $request)
    {
        // Get authorizated user details
        $user = auth()->user();

        if($request->player_id !='' && $request->external_user_id!='')
        {

            if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
                $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();
            else if($user->user_role == Config::get('app.Management_role'))
                $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->pluck('id')->first();
            else if($user->user_role == Config::get('app.Staff_role'))
                $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->pluck('id')->first();
            else if($user->user_role == Config::get('app.Parent_role'))
                $user_table_id = UserParents::where(['user_id'=>$user->user_id])->pluck('id')->first();//fetch id from user all table to store notification triggered user
            $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();

            $check_exists = Appusers::where('loginid',$userall_id)->get()->toArray();
            if(empty($check_exists))
            {
	            $data = ([
	                'loginid'=>$userall_id,
	                'player_id'=>($request->player_id!='')?$request->player_id:'',
	                'external_user_id'=>($request->external_user_id)?$request->external_user_id:'',
	                'device_type'=>($request->device_type)?$request->device_type:'',
	                'device_name'=>($request->device_name)?$request->device_name:'',
	                'device_version'=>($request->device_version)?$request->device_version:'',
	                'app_version'=>($request->app_version)?$request->app_version:'',
	                'login_date'=>Carbon::now()->timezone('Asia/Kolkata'),
	               	'created_on'=>Carbon::now()->timezone('Asia/Kolkata'),
	            ]);
            	Appusers::insert($data);
	        }
	        else
	        {
	        	Appusers::where(['loginid'=>$userall_id])->update(['player_id'=>($request->player_id!='')?$request->player_id:'','external_user_id'=>$request->external_user_id,'device_type'=>$request->device_type,'device_name'=>$request->device_name,'device_version'=>$request->device_version,'app_version'=>$request->app_version,'login_date'=>Carbon::now()->timezone('Asia/Kolkata'),'created_on'=>Carbon::now()->timezone('Asia/Kolkata')]);
	        }
            return response()->json('Device details registered Successfully');
        }
        else
            return response()->json('Player ID is required');
    }

    // List the subjects for class 
    public function class_subjects_list(Request $request)
    {
    	// Save last login in DB
        $userdata = auth()->user();
        $division_id = $request->division_id; //get division id input
        $class_subjects_review=[];
        $classessections = AcademicClassConfiguration::select('id','class_id','section_id','division_id')->where('division_id',$division_id)->get(); //fetch class and sections list for input division

        if(!empty($classessections)) //check whether configuration empty or not
        {
	        foreach ($classessections as $key => $value) { 
	        	// arrange necesary details in array
	        	$classsection_data[$value->id]=([
	        		'class_section_id'=>$value->id,
	        		'class_section_name'=>$value->classsectionName(),
	        		'division_id'=>$value->division_id,
	        	]);
	        	// fetch subjects for corresponding config class 
	        	$subjects_list = AcademicSubjectsMapping::where('class_config',$value->id)->get();

				foreach ($subjects_list as $subject_key => $subject_value) {
			        $subjects[$value->id][]= ([
		        		'subject_id'=>$subject_value->id,
		        		'subject_name'=>$subject_value->subjectName()
		        	]);
				}
	        }
	        $index = 0;
	        foreach ($classsection_data as $class_key => $class_value) {//merge class and subject related details
	        	$class_section_review[$index] = isset($classsection_data[$class_value['class_section_id']])?$classsection_data[$class_value['class_section_id']]:[];
	        	$class_section_review[$index]['subjects'] = isset($subjects[$class_value['class_section_id']])?$subjects[$class_value['class_section_id']]:[];
	        	$index++;
	        }
	       
	    }
	    return response()->json($class_section_review);
    }

    // Create and update users
    public function create_update_users(Request $request)
    {
    	// Check authenticate user
        $user = auth()->user();

        // get user details from table
        $user_details =  app('App\Http\Controllers\APILoginController')->get_user_table_id($user);

        $user_table_id = $user_details->id; //fetch particular id

        $userall_id = $loginid = $user_id = $image = ''; //declaration

        $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first(); //fetch common id

        $role = $request->user_role;

        if($userall_id!='') //check common id is exists
        {
        	//select and Store user details based on user role
        	if($role == Config::get('app.Admin_role')) 
        	{
        		if($request->id!='')
        			$individual_user_details = UserAdmin::where('id',$request->id)->first();
        		else
        			$individual_user_details = new UserAdmin();

        		$target_file = '/admin/';
        	}
        	else if($role == Config::get('app.Management_role')) 
        	{
        		if($request->id!='')
        			$individual_user_details = UserManagements::where('id',$request->id)->first();
        		else
        			$individual_user_details = new UserManagements();

        		$target_file = '/management/';
        	}
        	else if($role == Config::get('app.Staff_role')) 
        	{
        		if($request->id!='')
        			$individual_user_details = UserStaffs::where('id',$request->id)->first();
        		else
        			$individual_user_details = new UserStaffs();

        		$target_file = '/staff/';
        		$user_category = ($request->id!='')?$individual_user_details->user_category:'';
        	}
        	else
        		return response()->json(['status'=>false,'message'=>"You don't have a permission to create user!..."]);

        	if($request->id!='')// fetch selected management user details 
        	{
	    		$loginid = $individual_user_details->id;
				$user_id = $individual_user_details->user_id;
			}

			if(isset($request->employee_no) && $request->employee_no!='') //check employee no already exists or not
	        {
	        	$check_exists = $this->checkEmployeeno($loginid,$role,$request->employee_no);
	        	if($check_exists)
	        		 return response()->json(['status'=>false,'message'=>'Given Employee no already exists!...']);
	        }
	        if(isset($request->mobile_number) && $request->mobile_number!='') //check mobile no already exists or not
	        {
	        	$check_exists = $this->checkmobileno($loginid,$role,$request->mobile_number);
	        	if($check_exists)
	        		 return response()->json(['status'=>false,'message'=>'Given Mobile no already exists!...']);
	        }

			$individual_user_details->updated_by=$userall_id;
    		$individual_user_details->updated_time=Carbon::now()->timezone('Asia/Kolkata');
    		$individual_user_details->created_by=$userall_id;
        	$individual_user_details->created_time=Carbon::now()->timezone('Asia/Kolkata');

        	$schoolcode = $school_profile = SchoolProfile::where(['id'=>$user['school_profile_id']])->first();//get school code from school profile

	    	if($request->photo!='')
	        {
	        	$image = app('App\Http\Controllers\WelcomeController')->profile_file_upload($school_profile['school_code'],$request->photo,$request->attachment_type,$target_file,$request->ext);
	        }

	    	// if(count($_FILES)>0) //upload image
	        // {
	        //     if($request->hasfile('photo')) {
	        //         $image = app('App\Http\Controllers\WelcomeController')->profile_file_upload($school_profile['school_code'],$request->file('photo'),$request->attachment_type,$target_file);
	        //     }           
	        // }

	        $individual_user_details->first_name= $request->name;
	        $individual_user_details->mobile_number=$request->mobile_number;
	        if($image!='')
	        	$individual_user_details->profile_image = $image;
	        $individual_user_details->email_id=$request->email_address;
	        if($request->employee_no!='' && $request->employee_no!= null)
	       		$individual_user_details->employee_no=$request->employee_no;
	       	if($request->user_category!='' && $request->user_category!= null)
	       		$individual_user_details->user_category=$request->user_category;
	        $individual_user_details->dob=date('Y-m-d',strtotime($request->dob));
	        if($request->doj!='' && $request->doj!= null)
	        	$individual_user_details->doj=date('Y-m-d',strtotime($request->doj));

	        if($role == Config::get('app.Staff_role'))
	        {
	        	// Remove the subject and department related details for non-teaching staffs.
				if($request->user_category == 4) //4- non teaching
				{
					$individual_user_details->specialized_in = null;
					$individual_user_details->department = null;
				}
				
	        }
	        $individual_user_details->save();

	        $loginid = $individual_user_details->id;

	        if($request->id=='') //if new user, update user id and insert record in user common table
        	{
        		$role_code = ($role == Config::get('app.Admin_role'))?'A':(($role == Config::get('app.Management_role'))?'M':'T');
	        	// generate and update staff id in db 
	            $user_id = $school_profile->school_code.substr($school_profile->active_academic_year, -2).$role_code.sprintf("%04s", $loginid);

	            $individual_user_details->user_id = $user_id;
	            $individual_user_details->save();

	            $user_all = new UserAll;
	            $user_all->user_table_id=$loginid;
	            $user_all->user_role=$role;
	            $user_all->save();
        	}

        	$schoolusers = SchoolUsers::where('user_id',$user_id)->first(); //update email address in common login table

	        if($request->id=='')
	        {
	        	// given access to the groups based on the role  
	        	$all_group_ids = [];
	        	if($role == Config::get('app.Admin_role')) 
	        	{
	            	$all_group_ids = UserGroups::where('id','!=',1)->pluck('id')->toArray();
	            	$group_access = Config::get('app.Group_Active');
	        	}
	        	else if($role == Config::get('app.Management_role'))
	        	{
	        		$all_group_ids = UserGroups::pluck('id')->toArray();
	        		$group_access = Config::get('app.Group_Active');
	        	}
	        	else if($role == Config::get('app.Staff_role'))
	        	{
	        		$all_group_ids = ([2,3]);
	        		$group_access = Config::get('app.Group_Active');
	        	}

		        foreach($all_group_ids as $group_key => $group_id)
		        {
		        	UserGroupsMapping::insert(['group_id'=>$group_id,'user_table_id'=>$loginid,'user_role'=>$role,'group_access'=>$group_access,'user_status'=>Config::get('app.Group_Active')]);
		        }

		        // update user details in config DB.
	            $schoolusers = new SchoolUsers;
	            $schoolusers->school_profile_id=$user->school_profile_id;
	            $schoolusers->user_id=$user_id;
	            $schoolusers->user_password=bcrypt($request->mobile_number);
	        	$schoolusers->user_role=$role;
	        	$schoolusers->user_status=Config::get('app.Group_Active');
	        }

	        $schoolusers->user_mobile_number=$request->mobile_number;
	        $schoolusers->user_email_id=$request->email_address;
	        $schoolusers->save();

	        if($role == Config::get('app.Staff_role'))
	        {
		        if($request->user_category == 3 && $user_category != $request->user_category)
		        {             
		        	// remove the "Non-teaching group if user changed their role to teaching staff." 
		            UserGroupsMapping::where('user_role',Config::get('app.Staff_role'))->where('group_id',5)->where('user_table_id',$individual_user_details->id)->delete();
		            // checking teaching staff group alreay mapped with the user.
		            $check_exists_nonteaching = UserGroupsMapping::where('user_role',Config::get('app.Staff_role'))->where('group_id',4)->where('user_table_id',$individual_user_details->id)->first();
		            if(empty($check_exists_nonteaching)) //if not given access to user
		                UserGroupsMapping::insert(['user_role'=>Config::get('app.Staff_role'),'group_id'=>4,'user_table_id'=>$individual_user_details->id,'group_access'=>2]);
		        }
		        else if($request->user_category == 4 && $user_category != $request->user_category)
		        {
		        	// remove the " teaching staff group if user changed their role to Non-teaching." 
		            UserGroupsMapping::where('user_role',Config::get('app.Staff_role'))->where('group_id',4)->where('user_table_id',$loginid)->delete();
		            // checking Non-teaching staff group alreay mapped with the user.
		            $check_exists_nonteaching = UserGroupsMapping::where('user_role',Config::get('app.Staff_role'))->where('group_id',5)->where('user_table_id',$individual_user_details->id)->first();
		            if(empty($check_exists_nonteaching))//if not given access to user
		                UserGroupsMapping::insert(['user_role'=>Config::get('app.Staff_role'),'group_id'=>5,'user_table_id'=>$individual_user_details->id,'group_access'=>2]);

		            // remove access from the subject teacher
		            AcademicSubjectsMapping::where('staff',$individual_user_details->id)->update(['staff'=>null]);
		            // remove access from the class teacher
		            AcademicClassConfiguration::where('class_teacher',$individual_user_details->id)->update(['class_teacher'=>null]);

		            // check user have whole group access
		            $staff_group_list = UserGroups::where('group_type',2)->where('group_status',Config::get('app.Group_Active'))->pluck('id')->toArray();

		            UserGroupsMapping::where('user_role',Config::get('app.Staff_role'))->whereIn('group_id',$staff_group_list)->where('user_table_id',$individual_user_details->id)->delete();
		        }
	        }
			
			$rolename = (Config::get('app.Admin_role') == $role)?'Admin':((Config::get('app.Management_role') == $role)?'Management':'Staff');
			if($request->id=='')
	            return response()->json(['status'=>true,'message'=>$rolename.' user added Successfully!...']);
	       	else
	            return response()->json(['status'=>true,'message'=>$rolename.' details updated Successfully!...']);
        }
        else
        	return response()->json(['status'=>false,'message'=>'Invalid Credentails!...']);
    }

    // Check employee no
    public function checkEmployeeno($id,$user_role,$employee_no)
    {
    	if($user_role == Config::get('app.Admin_role'))
        	$check_exists = UserAdmin::where('employee_no',$employee_no);
        else if($user_role == Config::get('app.Staff_role'))
        	$check_exists = UserStaffs::where('employee_no',$employee_no);
        else if($user_role == Config::get('app.Management_role'))
        	$check_exists = UserManagements::where('employee_no',$employee_no);
        else 
        	return false;

        if($id!='')
            $check_exists = $check_exists->where('id','!=',$id);

        $check_exists = $check_exists->first();

        if(!empty($check_exists))
            return true;
        else
            return false;
    }
    // Check employee no
    public function checkmobileno($id,$user_role,$employee_no)
    {
    	if($user_role == Config::get('app.Admin_role'))
        	$check_exists = UserAdmin::where('mobile_number',$employee_no);
        else if($user_role == Config::get('app.Staff_role'))
        	$check_exists = UserStaffs::where('mobile_number',$employee_no);
        else if($user_role == Config::get('app.Management_role'))
        	$check_exists = UserManagements::where('mobile_number',$employee_no);
        else 
        	return false;
        
        if($id!='')
            $check_exists = $check_exists->where('id','!=',$id);

        $check_exists = $check_exists->first();

        if(!empty($check_exists))
            return true;
        else
            return false;
    }

    /*------------------------------Onboarding Manual-----------------------------*/

    // create and update division
    public function create_update_division_manual(Request $request)
    {
    	$user_data = auth()->user();
    	 // get user details from table
        $user_details =  app('App\Http\Controllers\APILoginController')->get_user_table_id($user_data);

        $userall_id = UserAll::where(['user_table_id'=>$user_details->id,'user_role'=>$user_data->user_role])->pluck('id')->first();//fetch id from user all table to store notification triggered user

		$inserted_records=0;
        $status = 'insert';
		foreach ($request->divisions as $key => $value) {
			$check_exists = AcademicDivisions::where(['division_name'=>$value['division_name']])->pluck('id')->first(); //check whether the given sub-division name already exists ro not

			if(isset($value['division_id']) && $value['division_id']!='' && $check_exists=='')        
			{
				$academic_division = AcademicDivisions::where(['id'=>$value['division_id']])->first();//if already exists update the details
	            $academic_division->updated_by = $userall_id;
	            $academic_division->updated_time = Carbon::now()->timezone('Asia/Kolkata');
			}		
        	else
                $academic_division = new AcademicDivisions;//insert record if new sub-division 
            
    		$academic_division->division_name = $value['division_name'];
            $academic_division->created_time = Carbon::now()->timezone('Asia/Kolkata');
            $academic_division->created_by = $userall_id;
            $academic_division->save();

           	if((isset($value['division_id']) && $value['division_id']!='') || $check_exists!='')
            	$status = 'edit';
		}

    	Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['division'=>1]);

    	return response()->json(['status'=>true,'message'=>'Division '.$status.' Successfully!...']);
    }

    // get division list
	public function get_divisions()
	{
		$divisions = AcademicDivisions::select('id','division_name')->get()->toArray();
		return response()->json(compact('divisions'));
	}

    // Delete division
	public function delete_division(Request $request)
	{
		if(isset($request->division_id) && $request->division_id!='') //check input exist
		{
			// Delete records 
	        AcademicSubjects::where('division_id',$request->division_id)->delete();
	        AcademicSections::where('division_id',$request->division_id)->delete();
	       	AcademicClasses::where('division_id',$request->division_id)->delete();
	        AcademicClassConfiguration::where('division_id',$request->division_id)->delete();
	        AcademicDivisions::where('id',$request->division_id)->delete();
		}
		return response()->json(['status'=>true,'message'=>'Deleted Successfully!...']);
	}

	//create and update section
	public function create_update_section_manual(Request $request)
	{
		$user_data = auth()->user();
    	 // get user details from table
        $user_details =  app('App\Http\Controllers\APILoginController')->get_user_table_id($user_data);

        $userall_id = UserAll::where(['user_table_id'=>$user_details->id,'user_role'=>$user_data->user_role])->pluck('id')->first();//fetch id from user all table to store notification triggered user

		$sections = $request->sections;
		$division_id = $request->division_id;
		foreach ($sections as $key => $value) {
			$section_id = AcademicSections::where(['section_name'=>$value['section_name'],'division_id'=>$division_id])->pluck('id')->first(); //check given section name is already exists or not

            if(!isset($value['section_id']) && $section_id=='')
                $academicsections = new AcademicSections;// updated sections along with sub-division
            else
            {
                $academicsections = AcademicSections::where(['id'=>$value['section_id'],'division_id'=>$division_id])->first();// insert sections along with sub-division details
	            $academicsections->updated_by = $userall_id;
	            $academicsections->updated_time = Carbon::now()->timezone('Asia/Kolkata');
            }

            $academicsections->section_name = $value['section_name'];
            $academicsections->division_id = $division_id;
            $academicsections->created_by = $userall_id;
            $academicsections->created_time = Carbon::now()->timezone('Asia/Kolkata');
            $academicsections->save();
            
            $section_id = $academicsections->id;
        	if(isset($value['section_id']) && $value['section_id']!='')
               	$status = 'edit';
        }
	    	
	    Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['sections'=>1]);

	    return response()->json(['status'=>true,'message'=>'Section '.$status.' Successfully!...']);
	}

	// Fetch Sections list
	public function get_sections(Request $request)
	{
		$sections = AcademicSections::select('id','section_name')->where('division_id',$request->division_id)->get()->toArray();
		foreach ($sections as $key => $value) {
			$sections[$key]=$value;
			$sections[$key]['isclicked']=false;
		}
		return response()->json(compact('sections'));
	}

	// Delete section
	public function delete_section(Request $request)
	{
		if(isset($request->division_id) && $request->division_id!='' && isset($request->section_id) && $request->section_id!='')
		{
			$classconfig = AcademicClassConfiguration::select('id')->where(['section_id'=> $request->section_id,'division_id'=>$request->division_id])->get()->toArray();

	       	AcademicSubjectsMapping::whereIn('class_config',$classconfig)->delete();
	        CommunicationDistribution::whereIn('class_config_id',$classconfig)->delete();
			UserStudents::whereIn('class_config',$classconfig)->delete();
	        AcademicClassConfiguration::whereIn('id',$classconfig)->delete();
	        AcademicSections::where('id',$request->section_id)->delete();
		}
		return response()->json(['status'=>true,'message'=>'Deleted Successfully!...']);
	}

	// create and update classes
	public function create_update_class_manual(Request $request)
	{
		$user_data = auth()->user();
    	 // get user details from table
        $user_details =  app('App\Http\Controllers\APILoginController')->get_user_table_id($user_data);

        $userall_id = UserAll::where(['user_table_id'=>$user_details->id,'user_role'=>$user_data->user_role])->pluck('id')->first();//fetch id from user all table to store notification triggered user
        $status = 'insert';
		$classes = $request->classes;
        $division_id = $request->division_id;
        foreach ($classes as $key => $value) {
        	$class_id ='';
        	if(!isset($value['class_id']))
        		$class_id = AcademicClasses::where(['class_name'=>$value['class_name'],'division_id'=>$division_id])->pluck('id')->first(); //check given class name is already exists or not
        	if(isset($value['class_id']) && $value['class_id']!='' && $class_id=='')
        	{
        		$class_details = AcademicClasses::where(['id'=>$value['class_id'],'division_id'=>$division_id])->first();// updated classes along with sub-division
	            $class_details->updated_by = $userall_id;
	            $class_details->updated_time = Carbon::now()->timezone('Asia/Kolkata');
        	}
        	else
                $class_details = new AcademicClasses;// insert classes along with sub-division details

    		$class_details->class_name = $value['class_name'];
    		$class_details->division_id= $division_id;
            $academicclasses->created_by = $userall_id;
            $academicclasses->created_time = Carbon::now()->timezone('Asia/Kolkata');
            $class_details->save();

            if(!isset($value['class_id']) && $class_id !='')
            	$status = $this->map_classes_sections($userall_id); //Map classes and sections
            if(isset($value['class_id']) && $value['class_id']!='')
            	$status = 'edit';
        }
        Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['classes'=>1]);


        // Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['map_classes_sections'=>1]); //update completion status in configuration table
        return response()->json(['status'=>true,'message'=>'Classes '.$status.' Successfully!...']);
	}

	public function map_classes_sections($userall_id) //map classes and section - on-boarding
	{
		$user_data = auth()->user(); //check authentication
		$academicyear = SchoolAcademicYears::where(['school_profile_id'=>$user_data->school_profile_id])->pluck('academic_year')->first();//fetch academic year
		$classes = AcademicClasses::select('id','division_id')->get()->toArray(); //get all classes id and division 
		if(!empty($classes)) //check empty
		{
			foreach ($classes as $key => $value) {
				$sections =AcademicSections::select('id')->where('division_id',$value['division_id'])->get()->toArray(); //fetch all the sections inside selected sub-division id
				if(!empty($sections))
				{
					foreach ($sections as $sec_key => $sec_value) {
						$classconfig = AcademicClassConfiguration::where(['class_id'=>$value['id'],'section_id'=>$sec_value['id']])->pluck('id')->first(); //check the configuration already exist in db
						if($classconfig=='') //not exists means,insert configuration (map)
		            	{
			        		$class_config = new AcademicClassConfiguration;
		                    $class_config->academic_year = $academicyear;
		                    $class_config->class_id = $value['id'];
		                    $class_config->section_id = $sec_value['id'];
		                    $class_config->division_id=$value['division_id'];
		                    $class_config->created_by=$userall_id;
		                    $class_config->created_time=Carbon::now()->timezone('Asia/Kolkata');
		                    $class_config->save();
		                    $class_config_id = $class_config->id;
		            	}
					}
				}
	        }
	    }	
	}

	public function get_classes() //check inserted classes review (on-boarding)
	{
		// Save last login in DB
        $userdata = auth()->user();
        $classes = AcademicClasses::select('id','class_name','division_id')->get(); //fetch all the classes and form in array format
        foreach ($classes as $key => $value) {
        	$class_review[]=([
        		'id'=>$value->id,
        		'class_name'=>$value->class_name,
        		'division_id'=>$value->division_id,
        		'division_name'=>$value->divisionName(),
        	]);
        }
        return response()->json($class_review);
	}

	public function get_class_section(Request $request) //check inserted classes and sections review (on-boarding)
	{
		// Save last login in DB
        $userdata = auth()->user();
        $division_id = $request->division_id;
        $class_section_review=[];
        $classessections = AcademicClassConfiguration::select('id','class_id','section_id')->where('division_id',$division_id)->get(); //fetch all mapped classes and section from table
        if(!empty($classessections))
        {
	        foreach ($classessections as $key => $value) { //formed mulit-dimenisional array along with sections
	        	$section_details = $value->sectionName();

	        	$classsection_data[$value->class_id]=([
	        		'id'=>$value->id,
	        		'class_id'=>$value->class_id,
	        		'class_name'=>$value->className(),
	        		'division_id'=>$section_details->division_id,
	        		'division_name'=>$section_details->divisionName(),
	        	]);

	        	$sections[$value->class_id][]= ([
	        		'section_id'=>$value->section_id,
	        		'section_name'=>$section_details->section_name
	        	]);
	        }
	        $index = 0;
	        foreach ($classsection_data as $class_key => $class_value) {
	        	$class_section_review[$index] = $classsection_data[$class_value['class_id']];
	        	$class_section_review[$index]['sections'] = $sections[$class_value['class_id']];
	        	$index++;
	        }
	       
	    }
	    return response()->json($class_section_review);
	}

	public function get_selected_class_section(Request $request) //list all classes and sections for mapping(on-boarding)
	{
		$sections_list = $selected_section= [];
		$classconfig = AcademicClassConfiguration::select('section_id')->where(['class_id'=> $request->class_id,'division_id'=>$request->division_id])->get()->toArray();

		$sections = AcademicSections::select('id','section_name')->where(['division_id'=>$request->division_id])->get()->toArray();
		if(!empty($sections))
		{
			if(!empty($classconfig))
				$selected_section = array_column($classconfig, 'section_id');

			foreach ($sections as $key => $value) {
				$sections_list[] = ([
					'id'=>$value['id'],
					'section_name'=>$value['section_name'],
					'is_checked'=>in_array($value['id'], $selected_section)
				]);
			}
		}
		return response()->json($sections_list);
	}

	// Delete class
	public function delete_class(Request $request)
	{
		if(isset($request->division_id) && $request->division_id!='' && isset($request->class_id) && $request->class_id!='')
		{
			$classconfig = AcademicClassConfiguration::select('id')->where(['class_id'=> $request->class_id,'division_id'=>$request->division_id])->get()->toArray();

	       	AcademicSubjectsMapping::whereIn('class_config',$classconfig)->delete();
	        CommunicationDistribution::whereIn('class_config_id',$classconfig)->delete();
			UserStudents::whereIn('class_config',$classconfig)->delete();
	        AcademicClassConfiguration::whereIn('id',$classconfig)->delete();
	        AcademicClasses::where('id',$request->class_id)->delete();
		}
		return response()->json(['message'=>'Deleted Successfully!...']);
	}
	
	public function delete_class_section(Request $request) //delete unchecked mapping (on-boarding)
	{
		$data = $request->data;
		if(!empty($data))
		{
			foreach ($data as $key => $value) {
				$classconfig = AcademicClassConfiguration::select('id')->where(['class_id'=> $request->class_id,'section_id'=>$value['section_id']])->get()->toArray();
				if(!empty($classconfig) && $value['is_checked'] == 'false')
				{
					$classconfig = array_column($classconfig,'id');
			        CommunicationDistribution::whereIn('class_config_id',$classconfig)->delete();
			        UserStudents::whereIn('class_config',$classconfig)->delete();
			        AcademicSubjectsMapping::whereIn('class_config',$classconfig)->delete();
			        AcademicClassConfiguration::whereIn('id',$classconfig)->delete();
			    }
			    else if($value['is_checked'] == 'true' && empty($classconfig)) 
			    {
			    	$user_data = auth()->user();

			        if($user_data->user_role == 1)
			            $user_admin = UserAdmin::where(['user_id'=>$user_data->user_id])->pluck('id')->first();

			        $userall_id = UserAll::where(['user_table_id'=>$user_admin,'user_role'=>$user_data->user_role])->pluck('id')->first(); //fetch id from user all table to store setting triggered user

			    	$academicyear = SchoolAcademicYears::where(['school_profile_id'=>$user_data->school_profile_id])->pluck('academic_year')->first();
			    	$class_config = new AcademicClassConfiguration;
                    $class_config->academic_year = $academicyear;
                    $class_config->class_id = $request->class_id;
                    $class_config->section_id = $value['section_id'];
                    $class_config->division_id=$request->division_id;
                    $class_config->created_by=$userall_id;
                    $class_config->created_time=Carbon::now()->timezone('Asia/Kolkata');
                    $class_config->save();
                    $class_config_id = $class_config->id;
			    }
			}
	        
		}
	    return response()->json(['message'=>'Submitted Successfully!...']);
	}

	public function subjects(Request $request)
	{
		$user_data = auth()->user();

		$subjects = $request->subjects;
		$division_id = $request->division_id;
		 // get user details from table
        $user_details =  app('App\Http\Controllers\APILoginController')->get_user_table_id($user_data);

        $userall_id = UserAll::where(['user_table_id'=>$user_details->id,'user_role'=>$user_data->user_role])->pluck('id')->first();//fetch id from user all table to store notification triggered user
        foreach ($subjects as $row=>$value) {

        	if($value['subject_name']!='')
        	{
        		if(isset($value['subject_id']) && $value['subject_id']!='')
        		{
        			$status = 'edit';

        			$subject_data = AcademicSubjects::where(['id'=>$value['subject_id'],'division_id'=>$division_id])->first(); //To check given subject name is already exists in DB.
        			$subject_data->updated_by=$userall_id;
            		$subject_data->updated_time=Carbon::now()->timezone('Asia/Kolkata');
        			
        		}
        		else
        		{
        			$status = 'insert';
        			if(isset($value['division_name']) && $value['division_name'] !='')
        				$division_id = AcademicDivisions::where(['division_name'=>$value['division_name']])->pluck('id')->first();
		        	$check_exists = AcademicSubjects::where(DB::raw('lower(subject_name)'), strtolower($value['subject_name']))->where('division_id',$division_id)->pluck('id')->first(); //To check given subject name is already exists in DB.
		            if($check_exists == '' && !in_array($value['subject_name'],$subject_list)) //if no then insert 
		            {
		                array_push($subject_list, $value['subject_name']);//check mobile number already exists in array

		                $subject_data = new AcademicSubjects;
		                $subject_data->created_by=$userall_id;
            			$subject_data->created_time=Carbon::now()->timezone('Asia/Kolkata');
		            }
		        }
		        // Prepare subjects array
                $subject_data->subject_name = $value['subject_name'];
                $subject_data->short_name = isset($value['short_name'])?$value['short_name']:'';
                $subject_data->division_id = $division_id;
            	$subject_data->save();
	        }
        }
       
    	Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['subjects'=>1]);

    	return response()->json(['status'=>true,'message'=>'Subjects '.$status.' Successfully!...']);
	}	

	//Class config details
	public function get_combine_class_section_list(Request $request)
	{
		$class_sections = [];
		$class_sections_list = AcademicClassConfiguration::select('id','class_id','section_id')->where('division_id',$request->division_id)->get();
		if(!empty($class_sections_list))
		{
			foreach ($class_sections_list as $key => $value) {
				$class_sections[] = ([
					'class_section'=>$value->classsectionName(),
					'id'=>$value->id
				]);
			}
		}
		return response()->json($class_sections);
	}

	// get subjects for edit
	public function get_edit_subjects(Request $request)
	{
		$subject_ids = AcademicSubjectsMapping::where('class_config',$request->class_config)->pluck('subject')->toArray();
		$subjects = AcademicSubjects::select('id','subject_name')->where('division_id',$request->division_id)->whereIn('id',$subject_ids)->get()->toArray();
		return response()->json($subjects);
	}
	
	// Map subjects to classes
	public function mapsubjects(Request $request)
	{
		$mapsubjects = $request->mapsubjects;//get all inputs
		 // get user details from table
        $user_details =  app('App\Http\Controllers\APILoginController')->get_user_table_id($user_data);

        $userall_id = UserAll::where(['user_table_id'=>$user_details->id,'user_role'=>$user_data->user_role])->pluck('id')->first();//fetch id from user all table to store notification triggered user
		if(!empty($mapsubjects))//check array is empty or not
		{
			foreach ($mapsubjects as $key => $value) { //process the input in loop
				$subjects = AcademicSubjectsMapping::select('id')->where(['class_config'=> $request->class_config,'subject'=>$value['subject_id']])->get()->toArray(); //fetch all data for corresponding class and subject
				if(!empty($subjects) && $value['is_checked'] == 'false') //check not empty and not checked 
				{
					$group_id = UserGroups::where('class_config',$request->class_config)->pluck('id')->first();//fetch group id to remove message dropped for that unselected subject.
			        Communications::where('group_id',$group_id)->where('subject_id',$value['subject_id'])->where('communication_type',2)->delete();//delete subject related message from homework
			        AcademicSubjectsMapping::where('id',$subjects['id'])->delete(); //delete subject mapping record
			    }
			    else if($value['is_checked'] == 'true' && empty($subjects)) //check its a new entry
			    {
			    	$subject_config = new AcademicSubjectsMapping;
                    $subject_config->subject = $value['subject_id'];
                    $subject_config->class_config = $request->class_config;
                    $subject_config->created_by=$userall_id;
                    $subject_config->created_time=Carbon::now()->timezone('Asia/Kolkata');
                    $subject_config->save();//insert new record if not exist already
			    }
			}
    		Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['map_subjects'=>1]);


    		return response()->json(['status'=>true,'message'=>'MapSubjects updated Successfully!...']);
		}
		return response()->json(['message'=>"Some inputs can't be empty!..."]);
	}

	// Get Subjects list 
	public function get_subjects(Request $request) //get all subjects list (on-boarding)
	{
		$subjectslist = $selected_subject =[];
		$classconfig = AcademicClassConfiguration::select('id')->where(['class_id'=> $request->class_id,'section_id'=>$request->section_id,'division_id'=>$request->division_id])->get()->toArray();

		$subjects = AcademicSubjectsMapping::select('subject')->where(['class_config'=> $classconfig])->get()->toArray();

		$subjects_list = AcademicSubjects::select('id','subject_name')->where(['division_id'=>$request->division_id])->get()->toArray();
		if(!empty($subjects_list))
		{
			if(!empty($subjects))
				$selected_subject = array_column($subjects, 'subject');

			foreach ($subjects_list as $key => $value) {
				$subjectslist[] = ([
					'id'=>$value['id'],
					'subject_name'=>$value['subject_name'],
					'is_checked'=>in_array($value['id'], $selected_subject)
				]);
			}
		}
		return response()->json($subjectslist);
	}

	// delete subject (onboarding)
    public function delete_subject(Request $request)
    {
    	// Check authenticate user
        $userdata = auth()->user();
        // reset to null with selected subject staffs
        UserStaffs::where('specialized_in',$request->subject_id)->update(['specialized_in'=>null]);
		// Delete the class mapping to the subject record
        AcademicSubjectsMapping::where('subject',$request->subject_id)->delete();
        // fetch subject related communication from table
        $communication_ids = Communications::where('subject_id',$request->subject_id)->get()->toArray();
        if(!empty($communication_ids))
        {
        	// get id from fetched details
        	$ids_list = array_column($communication_ids,'id');
        	//delete subject related records from communication recipients table
        	CommunicationRecipients::whereIn('communication_id',$ids_list)->delete();
        	CommunicationAttachments::whereIn('communication_id',$ids_list)->delete();
        	CommunicationDistribution::whereIn('communication_id',$ids_list)->delete();
        	HomeworkParentStatus::whereIn('notification_id',$ids_list)->delete();
        	// delete subject related records from communication table
        	Communications::where('subject_id',$request->subject_id)->delete();
        }
        AcademicSubjects::where('id',$request->subject_id)->delete(); //delete staff record
        return response()->json(['status'=>true,'message'=>'Deleted Successfully!...']);
    }

    // fetch all staff details for onboarding process
    public function onboarding_staff_list()
    {
    	// Check authenticate user
        $userdata = auth()->user();

        $staff_list = UserStaffs::select('id','user_id','first_name','mobile_number','profile_image')->where('user_status',1)->get()->toArray(); //fetch all the staff for listing
        return response()->json($staff_list);
    }

    // Get single user details(onboarding)
    public function onboarding_fetch_single_staff(Request $request)
    {
    	// Check authenticate user
        $userdata = auth()->user();

        $check_class_teacher = AcademicClassConfiguration::where('class_teacher',$request->id)->first(); //check the user is a classteacher.
		
        $staff_list = UserStaffs::select('id','user_id','first_name','mobile_number','profile_image','specialized_in','user_category','email_id','dob','doj','employee_no')->where('user_status',1)->where('id',$request->id)->first(); //fetch all the staff for listing
        $staff_list->class_teacher = 'no'; //set default values
        $staff_list->class_config = 0; 
        $staff_list->specialized_in = (int)$staff_list->specialized_in;
        if(!empty($staff_list) && isset($check_class_teacher->class_teacher)) //check not empty for class configuration details
        {
        	$staff_list->class_teacher = 'yes';
        	$staff_list->class_config = $check_class_teacher->class_teacher; 

        }
        $subject_teacher =[];
        $subject_teacher_list = AcademicSubjectsMapping::where('staff',$request->id)->get()->toArray();
        if(!empty($subject_teacher_list))
        {
        	foreach($subject_teacher_list as $subj_key => $subj_value)
        	{
        		$subject_teacher[$subj_key]['class_config'] = $subj_value['class_config'];
        		$subject_teacher[$subj_key]['subject'] = $subj_value['subject'];
        	}
        }
        $staff_list->subject_teacher= $subject_teacher;
        return response()->json($staff_list);   
    }

    // delete staff (onboarding)
    public function onboarding_delete_staff(Request $request)
    {
    	// Check authenticate user
        $userdata = auth()->user();
        AcademicSubjectsMapping::where('staff',$request->id)->update(['staff'=>null]); //update assigned staff to null.
        UserStaffs::where('id',$request->id)->delete(); //delete staff record
        return response()->json(['status'=>true,'message'=>'Deleted Successfully!...']);
    }

    // edit staff details
    public function onboarding_edit_staff(Request $request)
    {
    	// Check authenticate user
        $user = auth()->user();

        $user_details =  app('App\Http\Controllers\APILoginController')->get_user_table_id($user);

        $userall_id = UserAll::where(['user_table_id'=>$user_details->id,'user_role'=>$user_data->user_role])->pluck('id')->first();//fetch id from user all table to store notification triggered user

        $profile_details = SchoolProfile::where(['id'=>$user->school_profile_id])->first();//Fetch school profile details 
        $staffs_details = [];
        if($request->id!='')// fetch selected staff details 
        	$staffs_details = UserStaffs::where('id',$request->id)->first();

        $schoolcode = $school_profile = SchoolProfile::where(['id'=>$user['school_profile_id']])->first();//get school code from school profile
    	$image ='';
    	$target_file = '/staff/';

        if(!empty($staffs_details))
        {
        	if($request->photo!='')
	        {
	        	$image = app('App\Http\Controllers\WelcomeController')->profile_file_upload($school_profile['school_code'],$request->photo,1,$target_file,$request->ext);
	        }
	        //save staff details
	        $staffs_details->first_name= $request->staff_name;
	        $staffs_details->mobile_number=$request->mobile_number;
	        if($image!='')
	        	$staffs_details->profile_image = ($image!='')?$image:'';
	        $staffs_details->email_id=$request->email_address;
	        $staffs_details->specialized_in=$request->specialized_in;
	        $staffs_details->user_category=$request->teacher_category;
	        $staffs_details->dob=$request->dob;
	        $staffs_details->doj=$request->doj;
	        $staffs_details->employee_no=$request->employee_no;
	        $staffs_details->updated_by=$userall_id;
	    	$staffs_details->updated_time=Carbon::now()->timezone('Asia/Kolkata');
	        $staffs_details->save();


	        $schoolusers = SchoolUsers::where('user_id',$staffs_details->user_id)->first(); //update email address in common login table

            $schoolusers->user_email_id=$request->email_address;
            $schoolusers->save();

            if($request->class_teacher_class_config!='')
            {
            	AcademicClassConfiguration::where('id',$request->class_teacher_class_config)->update(['class_teacher'=>$request->id]); //assign class teacher to class.

            	$class_techer_group = UserGroups::where('group_status',Config::get('app.Group_Active'))->where('class_config',$request->class_teacher_class_config)->pluck('id')->first();
            	$check_exists = UserGroupsMapping::where(['group_id'=>$class_techer_group,'user_table_id'=>$request->id,'user_role'=>Config::get('app.Staff_role'),'user_status'=>1])->pluck('id')->first();
             	if($check_exists=='')
            		UserGroupsMapping::insert(['group_id'=>$class_techer_group,'user_table_id'=>$request->id,'user_role'=>Config::get('app.Staff_role'),'user_status'=>1,'group_access'=>1]);
            }
            
            if(!empty($request->teacher_class_config))
            {
            	foreach ($request->teacher_class_config as $teacher_key => $teacher_value) {
             		AcademicSubjectsMapping::where('class_config',$teacher_value['class_config'])->where('subject_id',$teacher_value['subject_id'])->update(['staff'=>$request->id]); //assign staff to class.
             		$techer_group = UserGroups::where('group_status',Config::get('app.Group_Active'))->where('class_config',$teacher_value['class_config'])->pluck('id')->first();
             		$check_exists = UserGroupsMapping::where(['group_id'=>$techer_group,'user_table_id'=>$request->id,'user_role'=>Config::get('app.Staff_role'),'user_status'=>1])->pluck('id')->first();
             		if($check_exists=='')
            			UserGroupsMapping::insert(['group_id'=>$techer_group,'user_table_id'=>$request->id,'user_role'=>Config::get('app.Staff_role'),'user_status'=>1,'group_access'=>1]);
            	}
            }

            return response()->json(['status'=>true,'messgae'=>'Staff details updated Successfully!...']);
        }
        else
        {
        	if($request->photo!='')
	        {
	        	$image = app('App\Http\Controllers\WelcomeController')->profile_file_upload($school_profile['school_code'],$request->photo,1,$target_file,$request->ext);
	        }

        	//save staff details
            $staffs_details = new UserStaffs;
            $staffs_details->first_name= $request->staff_name;
            $staffs_details->mobile_number=$request->mobile_number;
            if($image!='')
            	$staffs_details->profile_image = ($image!='')?$image:'';
            $staffs_details->email_id=$request->email_address;
	        $staffs_details->specialized_in=$request->specialized_in;
	        $staffs_details->user_category=$request->teacher_category;
	        $staffs_details->dob=$request->dob;
	        $staffs_details->doj=$request->doj;
	        $staffs_details->employee_no=$request->employee_no;
            $staffs_details->created_by=$userall_id;
        	$staffs_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
            $staffs_details->save();

            $staff_id =$staffs_details->id; // staff id

            // generate and update staff id in db 
            $userstaff_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'T'.sprintf("%04s", $staff_id);
            $staffs_details->user_id = $userstaff_id;
            $staffs_details->save();

            $user_all = new UserAll;
            $user_all->user_table_id=$staff_id;
            $user_all->user_role=Config::get('app.Staff_role');
            $user_all->save();

            $schoolusers = new SchoolUsers;
            $schoolusers->school_profile_id=$user->school_profile_id;
            $schoolusers->user_id=$userstaff_id;
            $schoolusers->user_mobile_number=$request->mobile_number;
            $schoolusers->user_password=bcrypt($request->mobile_number);
            $schoolusers->user_role=Config::get('app.Staff_role');
            $schoolusers->user_status=1;
            $schoolusers->save();

            if($request->class_teacher_class_config!='')
            {
            	AcademicClassConfiguration::where('id',$request->class_teacher_class_config)->update(['class_teacher'=>$staff_id]); //assign class teacher to class.

            	$class_teacher_group = UserGroups::where('group_status',Config::get('app.Group_Active'))->where('class_config',$request->class_teacher_class_config)->pluck('id')->first();
            	$check_exists = UserGroupsMapping::where(['group_id'=>$class_teacher_group,'user_table_id'=>$staff_id,'user_role'=>Config::get('app.Staff_role'),'user_status'=>1])->pluck('id')->first();
             	if($check_exists=='')
            		UserGroupsMapping::insert(['group_id'=>$class_teacher_group,'user_table_id'=>$staff_id,'user_role'=>Config::get('app.Staff_role'),'user_status'=>1,'group_access'=>1]);
            }
            
            if(!empty($request->teacher_class_config))
            {
            	UserGroupsMapping::insert(['group_id'=>2,'user_table_id'=>$staff_id,'user_role'=>Config::get('app.Staff_role'),'user_status'=>1,'group_access'=>1]);
            	foreach ($request->teacher_class_config as $teacher_key => $teacher_value) {
             		AcademicSubjectsMapping::where('class_config',$teacher_value['class_config'])->where('subject_id',$teacher_value['subject_id'])->update(['staff'=>$staff_id]); //assign staff to class.
             		$techer_group = UserGroups::where('group_status',Config::get('app.Group_Active'))->where('class_config',$teacher_value['class_config'])->pluck('id')->first();
             		$check_exists = UserGroupsMapping::where(['group_id'=>$teacher_group,'user_table_id'=>$staff_id,'user_role'=>Config::get('app.Staff_role'),'user_status'=>1])->pluck('id')->first();
             		if($check_exists=='')
            			UserGroupsMapping::insert(['group_id'=>$techer_group,'user_table_id'=>$staff_id,'user_role'=>Config::get('app.Staff_role'),'user_status'=>1,'group_access'=>1]);
            	}
            }
        }

        Configurations::where('school_profile_id',$user->school_profile_id)->update(['map_staffs'=>1]);
        return response()->json(['status'=>true,'messgae'=>'Staff details inserted Successfully!...']);
    }

    // Fetch management list
	public function get_management_list() //get all the management for edit in on-boarding
	{
		$management_list = [];//empty array declaration
		$managements = UserManagements::select('*')->get()->toArray();//get management list
		if(!empty($managements)) //check not empty
		{
			foreach ($managements as $key => $value) {
				$management_list[] = ([
					'id'=>$value['id'],
					'management_person_name'=>$value['first_name'],
					'photo'=>$value['profile_image'],
					'mobile_number'=>$value['mobile_number'],
				]);

			}
		}
		return response()->json($management_list); //return management details 
	}

    // Add managment person in DB.
	public function get_management_designation()
	{
		$categories = UserCategories::select('id','category_name')->where('user_role',5)->get()->toArray();
		return response()->json($categories);
	}

    // Get single user details(onboarding)
    public function onboarding_fetch_single_management(Request $request)
    {
    	// Check authenticate user
        $userdata = auth()->user();

        $management_list = UserManagements::select('id','user_id','first_name','mobile_number','profile_image','user_category','email_id','dob','doj','employee_no')->where('user_status',1)->where('id',$request->id)->first(); //fetch all the management for listing
        return response()->json($management_list);
        
    }

    // delete managment (onboarding)
    public function onboarding_delete_management(Request $request)
    {
    	// Check authenticate user
        $userdata = auth()->user();
        UserManagements::where('id',$request->id)->delete(); //delete staff record
        return response()->json(['status'=>true,'message'=>'Deleted Successfully!...']);
    }

    // Store Staff/ Management details(on-boarding)
	public function onboarding_create_user(Request $request)
	{
		$user_data = auth()->user();

       	$user_details =  app('App\Http\Controllers\APILoginController')->get_user_table_id($user_data);

        $userall_id = UserAll::where(['user_table_id'=>$user_details->id,'user_role'=>$user_data->user_role])->pluck('id')->first();//fetch id from user all table to store notification triggered user

        // fetch academic year
        $academicyear = SchoolAcademicYears::where(['school_profile_id'=>$user_data->school_profile_id])->pluck('academic_year')->first();

        $profile_details = SchoolProfile::where(['id'=>$user_data->school_profile_id])->first();//Fetch school profile details 
        $data = $request->data;
        $role = $request->role;

        $usermobile_numbers=[];
        foreach ($data as $key => $value) {
        	$image ='';
        	if(Config::get('app.Management_role') == $role)
        		$target_file = '/management/';
        	else if(Config::get('app.Staff_role') == $role)
        		$target_file = '/staff/';
        	else
        		return response()->json(['status'=>false,'messgae'=>'Invalid Role!...']);

        	if(isset($value['photo']) && $value['photo']!='')
	        {
	        	$image = app('App\Http\Controllers\WelcomeController')->profile_file_upload($school_profile['school_code'],$value['photo'],1,$target_file,$value['ext']);
	        }
	        if(Config::get('app.Management_role') == $role)
        		$check_exists = UserManagements::where(['mobile_number'=>$value['mobile_number']])->first(); //To check given subject name is already exists in DB.
        	else if(Config::get('app.Staff_role') == $role)
        		$check_exists = UserStaffs::where(['mobile_number'=>$value['mobile_number']])->first(); //To check given subject name is already exists in DB.

            if(empty($check_exists) && !in_array($value['mobile_number'],$usermobile_numbers) ) //if no then insert 
            {
	        	//save staff details
	        	if(Config::get('app.Management_role') == $role)
	            	$user_details = new UserManagements;
	            else if(Config::get('app.Staff_role') == $role)
	            	$user_details = new UserStaffs;

	            $user_details->first_name= $value['name'];
	            $user_details->mobile_number=$value['mobile_number'];
	            if($image!='')
	            	$user_details->profile_image = ($image!='')?$image:'';
	            if(isset($value['dob']) && $value['dob']!='')
	        		$user_details->dob = $value['dob'];
	        	if(isset($value['doj']) && $value['doj']!='')
	        		$user_details->doj = $value['doj'];
	        	if(isset($value['employee_no']) && $value['employee_no']!='')
	        		$user_details->employee_no = $value['employee_no'];
	        	if(isset($value['user_category']) && $value['user_category']!='')
	        		$user_details->user_category=$value['user_category'];

	            $user_details->created_by=$userall_id;
	        	$user_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
	            $user_details->save();

	            $id =$user_details->id; // staff id

	            // generate and update staff id in db 
	            if(Config::get('app.Management_role') == $role)
        			$user_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'T'.sprintf("%04s", $id);
        		else if(Config::get('app.Staff_role') == $role)
	            	$user_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'M'.sprintf("%04s", $id);

	            $user_details->user_id = $user_id;
	            $user_details->save();

	            $user_all = new UserAll;
	            $user_all->user_table_id=$id;
	            $user_all->user_role=$role;
	            $user_all->save();

	            $schoolusers = new SchoolUsers;
	            $schoolusers->school_profile_id=$user_data->school_profile_id;
	            $schoolusers->user_id=$user_id;
	            $schoolusers->user_mobile_number=$value['mobile_number'];
	            $schoolusers->user_password=bcrypt($value['mobile_number']);
	            $schoolusers->user_role=$role;
	            $schoolusers->user_status=1;
	            $schoolusers->save();

	            if($role == Config::get('app.Staff_role') && $value['class_teacher_class_config']!='')
	            {
	            	AcademicClassConfiguration::where('id',$value['class_teacher_class_config'])->update(['class_teacher'=>$id]); //assign class teacher to class.

	            	$class_teacher_group = UserGroups::where('group_status',Config::get('app.Group_Active'))->where('class_config',$value['class_teacher_class_config'])->pluck('id')->first();
	            	$check_exists = UserGroupsMapping::where(['group_id'=>$class_teacher_group,'user_table_id'=>$id,'user_role'=>Config::get('app.Staff_role'),'user_status'=>1])->pluck('id')->first();
	             	if($check_exists=='')
	            		UserGroupsMapping::insert(['group_id'=>$class_teacher_group,'user_table_id'=>$id,'user_role'=>Config::get('app.Staff_role'),'user_status'=>1,'group_access'=>1]);
	            }
	            
	            if($role == Config::get('app.Staff_role') && !empty($value['teacher_class_config']))
	            {
	            	UserGroupsMapping::insert(['group_id'=>2,'user_table_id'=>$id,'user_role'=>Config::get('app.Staff_role'),'user_status'=>1,'group_access'=>1]);
	            	foreach ($value['teacher_class_config'] as $teacher_key => $teacher_value) {
	             		AcademicSubjectsMapping::where('class_config',$teacher_value['class_config'])->where('subject',$teacher_value['subject_id'])->update(['staff'=>$id]); //assign staff to class.
	             		$techer_group = UserGroups::where('group_status',Config::get('app.Group_Active'))->where('class_config',$teacher_value['class_config'])->pluck('id')->first();
	             		$check_exists = UserGroupsMapping::where(['group_id'=>$teacher_group,'user_table_id'=>$id,'user_role'=>Config::get('app.Staff_role'),'user_status'=>1])->pluck('id')->first();
	             		if($check_exists=='')
	            			UserGroupsMapping::insert(['group_id'=>$techer_group,'user_table_id'=>$id,'user_role'=>Config::get('app.Staff_role'),'user_status'=>1,'group_access'=>1]);
	            	}
	            }
	        }
        }
        if(Config::get('app.Management_role') == $role)
	        Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['management'=>1]);
        else if(Config::get('app.Staff_role') == $role)
			Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['staffs'=>1]);

		return response()->json(['status'=>true,'messgae'=>'Details inserted Successfully!...']);
	}

    // edit staff details
    public function onboarding_edit_management(Request $request)
    {
    	// Check authenticate user
        $user = auth()->user();

        $user_details =  app('App\Http\Controllers\APILoginController')->get_user_table_id($user);

        $userall_id = UserAll::where(['user_table_id'=>$user_details->id,'user_role'=>$user_data->user_role])->pluck('id')->first();//fetch id from user all table to store notification triggered user

        $profile_details = SchoolProfile::where(['id'=>$user->school_profile_id])->first();//Fetch school profile details 
        $mgnt_details = [];
        if($request->id!='')// fetch selected mgnt details 
        	$mgnt_details = UserManagements::where('id',$request->id)->first();

        $schoolcode = $school_profile = SchoolProfile::where(['id'=>$user['school_profile_id']])->first();//get school code from school profile
    	$image ='';
    	$target_file = '/management/';

        if(!empty($mgnt_details))
        {
        	if($request->photo!='')
	        {
	        	$image = app('App\Http\Controllers\WelcomeController')->profile_file_upload($school_profile['school_code'],$request->photo,1,$target_file,$request->ext);
	        }
	        //save mgnt details
	        $mgnt_details->first_name= $request->name;
	        $mgnt_details->mobile_number=$request->mobile_number;
	        if($image!='')
	        	$mgnt_details->profile_image = ($image!='')?$image:'';
	        $mgnt_details->email_id=$request->email_address;
	        $mgnt_details->user_category=$request->user_category;
	        $mgnt_details->dob=$request->dob;
	        $mgnt_details->doj=$request->doj;
	        $mgnt_details->employee_no=$request->employee_no;
	        $mgnt_details->updated_by=$userall_id;
	    	$mgnt_details->updated_time=Carbon::now()->timezone('Asia/Kolkata');
	        $mgnt_details->save();


	        $schoolusers = SchoolUsers::where('user_id',$mgnt_details->user_id)->first(); //update email address in common login table

            $schoolusers->user_email_id=$request->email_address;
            $schoolusers->save();

            return response()->json(['status'=>true,'messgae'=>'Management details updated Successfully!...']);
        }
        else
        {
        	if($request->photo!='')
	        {
	        	$image = app('App\Http\Controllers\WelcomeController')->profile_file_upload($school_profile['school_code'],$request->photo,1,$target_file,$request->ext);
	        }

        	//save mgnt details
            $mgnt_details = new UserManagements;
            $mgnt_details->first_name= $request->name;
            $mgnt_details->mobile_number=$request->mobile_number;
            if($image!='')
            	$mgnt_details->profile_image = ($image!='')?$image:'';
            $mgnt_details->email_id=$request->email_address;
	        $mgnt_details->user_category=$request->user_category;
	        $mgnt_details->dob=$request->dob;
	        $mgnt_details->doj=$request->doj;
	        $mgnt_details->employee_no=$request->employee_no;
            $mgnt_details->created_by=$userall_id;
        	$mgnt_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
            $mgnt_details->save();

            $id =$mgnt_details->id; // mgnt id

            // generate and update mgnt id in db 
            $usermgnt_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'M'.sprintf("%04s", $id);
            $mgnt_details->user_id = $usermgnt_id;
            $mgnt_details->save();

            $user_all = new UserAll;
            $user_all->user_table_id=$id;
            $user_all->user_role=Config::get('app.Management_role');
            $user_all->save();

            $schoolusers = new SchoolUsers;
            $schoolusers->school_profile_id=$user->school_profile_id;
            $schoolusers->user_id=$usermgnt_id;
            $schoolusers->user_mobile_number=$request->mobile_number;
            $schoolusers->user_password=bcrypt($request->mobile_number);
            $schoolusers->user_role=Config::get('app.Management_role');
            $schoolusers->user_status=1;
            $schoolusers->save();
        }

        Configurations::where('school_profile_id',$user->school_profile_id)->update(['management'=>1]);
        return response()->json(['status'=>true,'messgae'=>'Management details inserted Successfully!...']);
    }
    
    // fetch all parent details for onboarding process
    public function onboarding_parent_list()
    {
    	// Check authenticate user
        $userdata = auth()->user();

        $parent_list = UserParents::select('id','user_id','first_name','mobile_number','class_config','dob')->where('user_status',1)->get()->toArray(); //fetch all the staff for listing
        return response()->json($parent_list);
    }

    // Onboarding for individual parent view
    public function onboarding_fetch_single_parent(Request $request)
    {
    	$student_list = $parentsdata = $students= [];//empty array declaration
		$student_list = UserStudentsMapping::select('student')->where('parent',$request->id)->first(); //fetch student details from parent mapped data
		if(!empty($student_list))
		{
			$parent_list = UserStudentsMapping::select('parent')->where('student',$student_list->student)->get()->toArray(); //fetch all parent details from student id
			$students = UserStudents::where('id',$student_list->student)->first(); //get student related info
			$parents = array_column($parent_list,'parent'); //pick parent id alone
			foreach ($parents as $parent_key => $parent_value) { //form array with parent details
				$parent_data = UserParents::where('id',$parent_value)->first();
				$parentsdata[$parent_data->user_category] = $parent_data; 
			}
		}
		else if($request->id!='')
		{
			$parent_data = UserParents::where('id',$request->id)->first();
			$parentsdata[$parent_data->user_category] = $parent_data; 
		}

		$student_list = ([
			'student_id'=>isset($students->id)?$students->id:0,
			'student_name'=>isset($students->first_name)?$students->first_name:'',
			'father_mobile_number'=>isset($parentsdata[1])?$parentsdata[1]->mobile_number:0,
			'father_email_address'=>isset($parentsdata[1])?$parentsdata[1]->email_id:'',
			'father_name'=>isset($parentsdata[1])?$parentsdata[1]->first_name:'',
			'father_id'=>isset($parentsdata[1])?$parentsdata[1]->id:0,
			'mother_mobile_number'=>isset($parentsdata[2])?$parentsdata[2]->mobile_number:0,
			'mother_email_address'=>isset($parentsdata[2])?$parentsdata[2]->email_id:'',
			'mother_name'=>isset($parentsdata[2])?$parentsdata[2]->first_name:'',
			'mother_id'=>isset($parentsdata[3])?$parentsdata[3]->id:0,
			'guardian_mobile_number'=>isset($parentsdata[3])?$parentsdata[3]->mobile_number:0,
			'guardian_email_address'=>isset($parentsdata[3])?$parentsdata[3]->email_id:'',
			'guardian_name'=>isset($parentsdata[3])?$parentsdata[3]->first_name:'',
			'guardian_id'=>isset($parentsdata[3])?$parentsdata[3]->id:0,
			'admission_number'=>isset($students->first_name)?$students->admission_number:'',
			'roll_no'=>isset($students->roll_number)?$students->roll_number:0,
			'dob'=>isset($students->dob)?$students->roll_number:null,
			'doj'=>isset($students->dob)?$students->roll_number:null,
			'employee_no'=>isset($students->employee_no)?$students->employee_no:'',
			'gender'=>isset($students->roll_number)?(Config::get('app.'.$students->gender)):0,
			'photo'=>isset($students->profile_image)?$students->profile_image:'',
			'temporary_student'=>(isset($students->profile_image) && $students->class_config == null)?'yes':'no',
			'class_section'=>isset($students->class_config)?$students->class_config:0,
		]);
		

		return response()->json($student_list); //return student details 
    }

    // delete parent (onboarding)
    public function onboarding_delete_parent(Request $request)
    {
    	// Check authenticate user
        $userdata = auth()->user();
        $studentids = UserStudentsMapping::where('parent',$request->id)->pluck('student')->toArray(); //fetch student id
        $parentids = UserStudentsMapping::whereIn('student',$studentids)->pluck('parent')->toArray(); //fetch student id
        if(count($parentids))
        	UserStudents::whereIn('id',$studentids)->delete();
        UserStudentsMapping::where('parent',$request->id)->delete(); //delete parent mapping record
        UserParents::where('id',$request->id)->delete();
        return response()->json(['status'=>true,'message'=>'Deleted Successfully!...']);
    }

    // Add students in DB along with parents and guardian details
	public static function onboarding_create_students(Request $request)
	{
		$user_data = auth()->user();
		$profile_details = SchoolProfile::where(['id'=>$user_data->school_profile_id])->first();//Fetch school profile details 

		$user_details =  app('App\Http\Controllers\APILoginController')->get_user_table_id($user);

        $userall_id = UserAll::where(['user_table_id'=>$user_details->id,'user_role'=>$user_data->user_role])->pluck('id')->first();//fetch id from user all table to store notification triggered user

		$inserted_records=0;
		$data = $request->data;
		$usermobile_numbers = [];
        //Process each and every row ,insert all data in db
        foreach ($data as $row) {
        	$student_image = $group_id = $password ='';
        	$student_details = [];
            $father_check_exists = UserParents::where('mobile_number',$row['father_mobile_number'])->first(); //To check given mobile no is already exists in DB.

            $mother_check_exists = UserParents::where('mobile_number',$row['mother_mobile_number'])->first(); //To check given mobile no is already exists in DB.

            $guardian_check_exists = UserParents::where('mobile_number',$row['guardian_mobile_number'])->first(); //To check given mobile no is already exists in DB.

        	if($row['student_name']!='' && $row['admission_number']!='')
            {
	        	$target_file = '/students/';
	        	if($row['student_photo']!='')
		        {
		        	$student_image = app('App\Http\Controllers\WelcomeController')->profile_file_upload($school_profile['school_code'],$row['student_photo'],1,$target_file,$row['student_ext']);
		        }

	        	$student_details = new UserStudents;

	            $student_details->first_name= $row['student_name'];
	            $student_details->admission_number=$row['admission_no'];
	            if(isset($row['roll_no']))
	            	$student_details->roll_number=$row['roll_no'];
	            if($student_image!='')
	            	$student_details->profile_image=$student_image;
	            $student_details->gender=$gender;
	            $student_details->class_config=$row['class_config'];
	            $student_details->dob=date('Y-m-d',strtotime($row['dob']));
	            $student_details->user_status=(isset($row['temporary_student']) && $request->temporary_student!='' && strtolower($row['temporary_student'])=='yes')?5:1;
	            $student_details->created_by=$userall_id;
	        	$student_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
	            $student_details->save();

	   			$student_id = $student_details->id;
	   			$password = '';
	   			// generate and update staff id in db 
	            $userstudent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'S'.sprintf("%04s", $student_id);
	            $student_details->user_id = $userstudent_id;
	            $student_details->save();
	            
	            if($profile_details->default_password_type == 'admission_number')
					$password = bcrypt($row['admission_no']);
				else if($profile_details->default_password_type == 'dob')
					$password = bcrypt(date('dmY',strtotime($row['dob'])));
	            
			}
			
            if(empty($father_check_exists) && !in_array($row['father_mobile_number'],$usermobile_numbers)) 
            {
            	array_push($usermobile_numbers, $row['father_mobile_number']);//check mobile number already exists in array
            	$father = [];
	        	$father['student_photo'] = $row['father_photo'];
	        	$father['first_name'] = $row['father_name'];
	        	$father['mobile_number'] = $row['father_mobile_number'];
	        	$father['email_address'] = $row['father_email_address'];
	        	$father['ext'] = $row['father_ext'];
	        	$father['user_category'] = 1;

	        	if($profile_details->default_password_type == 'mobile_number' || $password == '')
					$password = bcrypt($row['father_mobile_number']);
				
	        	$this->insert_parent_details($father,$student_details->id,$userall_id,$group_id,$password);
            }
            else
            {
            	if(!empty($student_details) && !empty($father_check_exists))
            		$this->createstudentmapping($student_details->id,$father_check_exists->id,$userall_id);
            }


            if(empty($mother_check_exists) && !in_array($row['mother_mobile_number'],$usermobile_numbers)) 
            {
            	array_push($usermobile_numbers, $row['mother_mobile_number']);//check mobile number already exists in array
            	$mother = [];
	        	$mother['student_photo'] = $row['mother_photo'];
	        	$mother['first_name'] = $row['mother_name'];
	        	$mother['mobile_number'] = $row['mother_mobile_number'];
	        	$mother['email_address'] = $row['mother_email_address'];
	        	$mother['ext'] = $row['mother_ext'];
	        	$mother['user_category'] = 2;

	        	if($profile_details->default_password_type == 'mobile_number' || $password == '')
					$password = bcrypt($row['mother_mobile_number']);
				
	        	$this->insert_parent_details($mother,$student_details->id,$userall_id,$group_id,$password);
            }
            else
            {
            	if(!empty($student_details) && !empty($mother_check_exists))	
            		$this->createstudentmapping($student_details->id,$mother_check_exists->id,$userall_id);
            }

            if(empty($guardian_check_exists) && !in_array($row['guardian_mobile_number'],$usermobile_numbers)) 
            {
            	array_push($usermobile_numbers, $row['guardian_mobile_number']);//check mobile number already exists in array
            	$guardian = [];
	        	$guardian['student_photo'] = $row['guardian_photo'];
	        	$guardian['first_name'] = $row['guardian_name'];
	        	$guardian['mobile_number'] = $row['guardian_mobile_number'];
	        	$guardian['email_address'] = $row['guardian_email_address'];
	        	$guardian['ext'] = $row['guardian_ext'];
	        	$guardian['user_category'] = 9;

	        	if($profile_details->default_password_type == 'mobile_number' || $password == '')
					$password = bcrypt($row['guardian_mobile_number']);
				
	        	$this->insert_parent_details($guardian,$student_details->id,$userall_id,$group_id,$password);
            }
            else
            {
            	if(!empty($student_details) && !empty($guardian_check_exists))
            		$this->createstudentmapping($student_details->id,$guardian_check_exists->id,$userall_id);
            }
        }   
	}

	public function createstudentmapping($id,$parent_id,$userall_id)
	{
		// mapping the student and parent
        $student_map = new UserStudentsMapping;
        $student_map->student = $id;  
        $student_map->parent = $parent_id;
        $student_map->created_by = $userall_id;
        $student_map->created_time=Carbon::now()->timezone('Asia/Kolkata');
        $student_map->save();

        return true;
	}

	// edit or insert parent (onboarding)
    public function onboarding_edit_parent(Request $request)
    {
    	// Check authenticate user
        $user = auth()->user();
		$user_details =  app('App\Http\Controllers\APILoginController')->get_user_table_id($user);

        $userall_id = UserAll::where(['user_table_id'=>$user_details->id,'user_role'=>$user_data->user_role])->pluck('id')->first();//fetch id from user all table to store notification triggered user

        $profile_details = SchoolProfile::where(['id'=>$user->school_profile_id])->first();//Fetch school profile details 
        $parent_details = $mother_details = $guardian_details = $student_details = [];

        $class_config_id = null;
        if(isset($request->gender))
        	$gender = (isset($request->gender) && strtolower($request->gender) == 'male')?1:((isset($request->gender) && strtolower($request->gender) == 'female')?2:3);

        //check image exists
   		$image ='';
   		$profile_image_path ='';

   		$target_file = '/students/';
    	if($request->student_photo!='')
        {
        	$profile_image_path = app('App\Http\Controllers\WelcomeController')->profile_file_upload($school_profile['school_code'],$request->student_photo,1,$target_file,$request->ext);
        }

        if($request->father_id>0 || $request->mother_id>0 || $request->guardian_id>0 || $request->student_id>0)// fetch check user already exists 
        {
        	$group_id =$old_group_id=$new_group_id='';

       		if($request->group_id!='')
       			$group_id = $request->group_id;

        	if(isset($request->student_id) && $request->student_id>0 && !isset($request->type))//arrange student details in array
        	{

        		$student_details = UserStudents::where(['id'=>$request->student_id])->first();
        		if($student_details->class_config != $request->class_config)
        		{
        			$old_group_id = UserGroups::where('class_config',$student_details->class_config)->pluck('id')->first();
        			$new_group_id = UserGroups::where('class_config',$request->class_config)->pluck('id')->first();
        		}

        	} 
        	else
                $student_details = new UserStudents;

            // student details insert or edit into db
            $student_details->first_name= $request->student_name;
            $student_details->admission_number=$request->admission_no;
            $student_details->roll_number=isset($request->roll_no)?$request->roll_no:'';
            if(!empty($profile_image_path) && !empty($request->student_photo))
            	$student_details->profile_image=$profile_image_path;
            if(isset($request->gender))
            	$student_details->gender=$gender;
            if(isset($request->dob))
            	$student_details->dob=date('Y-m-d',strtotime($request->dob));

            if(isset($student_details->class_config) && $student_details->class_config !='' && $request->class_config !='' && $student_details->class_config != $request->class_config && !isset($request->type))
            {
            	Attendance::where('user_table_id',$student_details->id)->where('class_config',$student_details->class_config)->update(['class_config'=>$request->class_config]);
            }

            $student_details->class_config=$request->class_config;

            $student_details->user_status=(isset($request->temporary_student) && $request->temporary_student!='' && strtolower($request->temporary_student)=='yes')?5:1;
            $student_details->updated_by=$userall_id;
        	$student_details->updated_time=Carbon::now()->timezone('Asia/Kolkata');
            $student_details->save();

   			$student_id = $student_details->id;

   			

   			// generate and update student id in db 
            $userstudent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'S'.sprintf("%04s", $student_id);
            $student_details->user_id = $userstudent_id;
            $student_details->save(); 

            // add into group
	        if($new_group_id!='' && ($old_group_id != $new_group_id) && $request->student_id!='' && !isset($request->type))
	        {
	        	$parent_ids = UserStudentsMapping::where('student',$request->student_id)->pluck('parent')->toArray();
	        	UserGroupsMapping::where(['group_id'=>$old_group_id,'user_role'=>Config::get('app.Parent_role')])->whereIn('user_table_id',$parent_ids)->delete();
	        	foreach ($parent_ids as $key => $parent_value) {
	        		UserGroupsMapping::insert(['group_id'=>$new_group_id,'user_table_id'=>$parent_value,'user_role'=>Config::get('app.Parent_role'),'user_status'=>1,'group_access'=>2]);
	        	}
	        }

            
            // insert parents details
            if(isset($request->father_id))
            {
	        	$father_details = UserParents::where('id',$request->father_id)->first();
		        if(!empty($father_details) || $request->father_mobile_number!='')
		        {
		        	$data['photo'] = $request->father_photo;
		        	$data['first_name'] = $request->father_name;
		        	$data['mobile_number'] = $request->father_mobile_number;
		        	$data['email_address'] = $request->father_email_address;
		        	$data['user_category'] = 1;

		        	$this->edit_parent_details($data,$father_details,$student_id,$userall_id,$old_group_id,$new_group_id);
		        }
		    }
	        // update or insert parents details
	        if(isset($request->mother_id))
            {
		        $mother_details = UserParents::where('id',$request->mother_id)->first();
		        if(!empty($mother_details) || $request->mother_mobile_number!='' )
		        {
		        	$data = [];
		        	$data['photo'] = $request->mother_photo;
		        	$data['first_name'] = $request->mother_name;
		        	$data['mobile_number'] = $request->mother_mobile_number;
		        	$data['email_address'] = $request->mother_email_address;
		        	$data['user_category'] = 2;

		        	$this->edit_parent_details($data,$mother_details,$student_id,$userall_id,$old_group_id,$new_group_id);
		        }
		    }

		    if(isset($request->guardian_id))
            {
	        // update or insert parents details
		        $guardian_details = UserParents::where('id',$request->guardian_id)->first();
		        if(!empty($guardian_details) || $request->guardian_mobile_number!='' )
		        {
		        	$data = [];
		        	$data['photo'] = $request->guardian_photo;
		        	$data['first_name'] = $request->guardian_name;
		        	$data['mobile_number'] = $request->guardian_mobile_number;
		        	$data['email_address'] = $request->guardian_email_address;
		        	$data['user_category'] = 3;

		        	$this->edit_parent_details($data,$guardian_details,$student_id,$userall_id,$old_group_id,$new_group_id);
		        }
		    }
	        Configurations::where('school_profile_id',$user->school_profile_id)->update(['students'=>1]);
	        return response()->json(['status'=>true,'message'=>'Student and parents details updated Successfully!...']);
	    }
        else
       	{  		
       		$group_id ='';

       		if($request->group_id!='')
       			$group_id = $request->group_id;
        	// insert student details
	        $student_details = new UserStudents;

            $student_details->first_name= $request->student_name;
            $student_details->admission_number=$request->admission_no;
            if(isset($request->roll_no))
            	$student_details->roll_number=isset($request->roll_no)?$request->roll_no:'';
            if(!empty($profile_image_path) && !empty($request->student_photo))
            	$student_details->profile_image=$profile_image_path;
            $student_details->gender=$gender;
            $student_details->class_config=$request->class_config;
            $student_details->dob=date('Y-m-d',strtotime($request->dob));
            $student_details->user_status=(isset($request->temporary_student) && $request->temporary_student!='' && strtolower($request->temporary_student)=='yes')?5:1;
            $student_details->created_by=$userall_id;
        	$student_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
            $student_details->save();

   			$student_id = $student_details->id;
   			$password = '';
   			// generate and update staff id in db 
            $userstudent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'S'.sprintf("%04s", $student_id);
            $student_details->user_id = $userstudent_id;
            $student_details->save();
            
            if($profile_details->default_password_type == 'admission_number')
				$password = bcrypt($request->admission_no);
			else if($profile_details->default_password_type == 'dob')
				$password = bcrypt(date('dmY',strtotime($request->dob)));

            // insert father details
            if($request->father_mobile_number!='' && $request->father_name!='')
        	{
        		$father_id = UserParents::where('mobile_number',$request->father_mobile_number)->pluck('id')->first();
        		if($father_id == '')
        		{
		        	$data = [];
		        	$data['photo'] = $request->father_photo;
		        	$data['first_name'] = $request->father_name;
		        	$data['mobile_number'] = $request->father_mobile_number;
		        	$data['email_address'] = $request->father_email_address;
		        	$data['user_category'] = 1;

		        	if($profile_details->default_password_type == 'mobile_number' || $password == '')
						$password = bcrypt($request->father_mobile_number);
					
		        	$this->insert_parent_details($data,$student_details->id,$userall_id,$group_id,$password);
        		}
        		else
        		{
        			 // add into group
			        if($group_id!='')
			        {
			        	UserGroupsMapping::insert(['group_id'=>$group_id,'user_table_id'=>$father_id,'user_role'=>Config::get('app.Parent_role'),'user_status'=>1]);
			        	UserGroupsMapping::insert(['group_id'=>2,'user_table_id'=>$father_id,'user_role'=>Config::get('app.Parent_role'),'user_status'=>1]);
			        }
			        
			        // mapping the student and parent
			        $student_map = new UserStudentsMapping;
			        $student_map->student = $student_details->id;  
			        $student_map->parent = $father_id;
			        $student_map->created_by = $userall_id;
			        $student_map->created_time=Carbon::now()->timezone('Asia/Kolkata');
			        $student_map->save();
        		}
	        }
	        // insert mother details
	        if($request->mother_mobile_number!='' && $request->mother_name!='')
        	{
        		$mother_id = UserParents::where('mobile_number',$request->mother_mobile_number)->pluck('id')->first();
        		if($mother_id == '')
        		{
		        	$data = [];
		        	$data['photo'] = $request->mother_photo;
		        	$data['first_name'] = $request->mother_name;
		        	$data['mobile_number'] = $request->mother_mobile_number;
		        	$data['email_address'] = $request->mother_email_address;
		        	$data['user_category'] = 2;

		        	if($profile_details->default_password_type == 'mobile_number' || $password == '')
						$password = bcrypt($request->mother_mobile_number);

		        	$this->insert_parent_details($data,$student_details->id,$userall_id,$group_id,$password);

	        	}
        		else
        		{
        			 // add into group
			        if($group_id!='')
			        {
			        	UserGroupsMapping::insert(['group_id'=>$group_id,'user_table_id'=>$mother_id,'user_role'=>Config::get('app.Parent_role'),'user_status'=>1]);
			        	UserGroupsMapping::insert(['group_id'=>2,'user_table_id'=>$father_id,'user_role'=>Config::get('app.Parent_role'),'user_status'=>1]);
			        }
			        			        
			        // mapping the student and parent
			        $student_map = new UserStudentsMapping;
			        $student_map->student = $student_details->id;  
			        $student_map->parent = $mother_id;
			        $student_map->created_by = $userall_id;
			        $student_map->created_time=Carbon::now()->timezone('Asia/Kolkata');
			        $student_map->save();
        		}
	        }

	        // insert guardian details
	        if($request->guardian_mobile_number!='' && $request->guardian_name!='')
        	{
        		$guardian_id = UserParents::where('mobile_number',$request->guardian_mobile_number)->pluck('id')->first();
        		if($guardian_id == '')
        		{
		        	$data = [];
		        	$data['photo'] = $request->guardian_photo;
		        	$data['first_name'] = $request->guardian_name;
		        	$data['mobile_number'] = $request->guardian_mobile_number;
		        	$data['email_address'] = $request->guardian_email_address;
		        	$data['user_category'] = 9;

		        	if($profile_details->default_password_type == 'mobile_number' || $password == '')
						$password = bcrypt($request->guardian_mobile_number);

		        	$this->insert_parent_details($data,$student_details->id,$userall_id,$group_id,$password);
	        	}
        		else
        		{
        			 // add into group
			        if($group_id!='')
			        {
			        	UserGroupsMapping::insert(['group_id'=>$group_id,'user_table_id'=>$guardian_id,'user_role'=>Config::get('app.Parent_role'),'user_status'=>1]);
			        	UserGroupsMapping::insert(['group_id'=>2,'user_table_id'=>$father_id,'user_role'=>Config::get('app.Parent_role'),'user_status'=>1]);
			        }
			        
			        // mapping the student and parent
			        $student_map = new UserStudentsMapping;
			        $student_map->student = $student_details->id;  
			        $student_map->parent = $guardian_id;
			        $student_map->created_by = $userall_id;
			        $student_map->created_time=Carbon::now()->timezone('Asia/Kolkata');
			        $student_map->save();
        		}
	        }
	        Configurations::where('school_profile_id',$user->school_profile_id)->update(['students'=>1]);
	        return response()->json(['status'=>true,'message'=>'Student and parents details inserted Successfully!...']);
       	}
    }

    // Edit parent details dependency function - onboarding
    public function edit_parent_details($data,$details,$id,$userall_id,$old_group_id,$new_group_id)
    {
    	$image =$page='';
    	$user = auth()->user();
    	$profile_image_path ='';
        $profile_details = SchoolProfile::where(['id'=>$user->school_profile_id])->first();//Fetch school profile details 
        $profile_image_path ='';

   		$target_file = '/students/';
    	if($request->student_photo!='')
        {
        	$profile_image_path = app('App\Http\Controllers\WelcomeController')->profile_file_upload($school_profile['school_code'],$request->student_photo,1,$target_file,$request->ext);
        }

    	if(empty($details) && !isset($details->mobile_number))
    	{
    		$page = 'new';
    	    $details = new UserParents;
    	}
    	
    	

        //save staff details
        if($data['first_name']!='')
        	$details->first_name= $data['first_name'];
        if($data['mobile_number']!='')
        	$details->mobile_number=$data['mobile_number'];
        if($profile_image_path!='')
        	$details->profile_image = ($profile_image_path!='')?$profile_image_path:'';
       	if($data['email_address']!='')
        	$details->email_id=$data['email_address'];
        if($data['user_category']!='')
        	$details->user_category = $data['user_category'];
        $details->updated_by=$userall_id;
    	$details->updated_time=Carbon::now()->timezone('Asia/Kolkata');
        $details->save();
        $parent_id = $details->id;
        if($page!='')
	    {
	    	// generate and update staff id in db 
            $userparent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'P'.sprintf("%04s", $parent_id);
            $details->user_id = $userparent_id;
            $details->save();     

            $user_all = new UserAll;
            $user_all->user_table_id=$parent_id;
            $user_all->user_role=Config::get('app.Parent_role');
            $user_all->save(); 

	    }
	    else
	    	$userparent_id = $details->user_id;

        if($data['email_address']!='')
        {
        	$schoolusers = SchoolUsers::where(['user_id'=>$userparent_id,'school_profile_id'=>$user->school_profile_id])->first();

        	if(empty($schoolusers))
            	$schoolusers = new SchoolUsers;

            $schoolusers->school_profile_id=$user->school_profile_id;
            $schoolusers->user_id=$userparent_id;
            $schoolusers->user_mobile_number=$data['mobile_number'];
            $schoolusers->user_password=bcrypt($data['mobile_number']);
            $schoolusers->user_email_id=$data['email_address'];
            $schoolusers->user_role=Config::get('app.Parent_role');
            $schoolusers->user_status=1;
            $schoolusers->save();
	    }
	    
	    if($page!='')
	    {
	        // mapping the student and parent
	        $student_map = new UserStudentsMapping;
	        $student_map->student = $id;  
	        $student_map->parent = $details->id;
	        $student_map->created_by = $userall_id;
	        $student_map->created_time=Carbon::now()->timezone('Asia/Kolkata');
	        $student_map->save();
	    }
    }

    // create parent details dependency function -onboarding
    public function insert_parent_details($data,$id,$userall_id,$group_id,$password)
    {
    	$user_data = auth()->user();
    	$profile_details = SchoolProfile::where(['id'=>$user_data->school_profile_id])->first();//Fetch school profile details 
  		$profile_image_path ='';

   		$target_file = '/parent/';
    	if($data['student_photo']!='')
        {
        	$profile_image_path = app('App\Http\Controllers\WelcomeController')->profile_file_upload($school_profile['school_code'],$data['student_photo'],1,$target_file,$data['ext']);
        }

    	// insert parent details in db
    	$parent=[];
        $parent_details = new UserParents;
    	$parent_details->created_by=$userall_id;
    	if($profile_image_path!='')
        	$parent_details->profile_image = $profile_image_path;
    	$parent_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
        $parent_details->mobile_number= $data['mobile_number'];
        $parent_details->user_category = $data['user_category'];
        $parent_details->first_name= $data['first_name'];
        $parent_details->email_id= $data['email_address'];
        $parent_details->user_status=1;//active
            
            
        $parent_details->save();

        $parent_id = $parent_details->id;

        // generate and update staff id in db 
        $userparent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'P'.sprintf("%04s", $parent_id);
        $parent_details->user_id = $userparent_id;
        $parent_details->save(); 

        // add into group
        if($group_id!='')
        {
        	UserGroupsMapping::insert(['group_id'=>2,'user_table_id'=>$parent_id,'user_role'=>Config::get('app.Parent_role'),'user_status'=>1,'group_access'=>2]);
        	UserGroupsMapping::insert(['group_id'=>$group_id,'user_table_id'=>$parent_id,'user_role'=>Config::get('app.Parent_role'),'user_status'=>1,'group_access'=>2]);
        }
        
        //make an entry in user all table
        $user_all = new UserAll;
        $user_all->user_table_id=$parent_details->id;
        $user_all->user_role=Config::get('app.Parent_role');
        $user_all->save(); 
            	
        // insert record in school user table
        $schoolusers = new SchoolUsers;

        $schoolusers->school_profile_id=$user_data->school_profile_id;
        $schoolusers->user_id=$userparent_id;
        $schoolusers->user_mobile_number=$data['mobile_number'];
        $schoolusers->user_password=$password;
        $schoolusers->user_role=Config::get('app.Parent_role');
        $schoolusers->user_status=1;
        $schoolusers->save();

        if($id!='' && $parent_id!='')
        {
	        // mapping the student and parent
	        $student_map = new UserStudentsMapping;
	        $student_map->student = $id;  
	        $student_map->parent = $parent_id;
	        $student_map->created_by = $userall_id;
	        $student_map->created_time=Carbon::now()->timezone('Asia/Kolkata');
	        $student_map->save();
        }
    }

    //Parent Category
	public function get_parent_category()
	{
		$categories = UserCategories::select('id','category_name')->where('user_role',3)->get()->toArray();
		return response()->json(compact('categories'));
	}

    /*----------------------------Onboarding Manual--------------------------------*/    

    // Add students in DB along with parents and guardian details (old need to remove)
	public static function students_excel_upload($data,$userall_id,$upload_type)
	{
		$user_data = auth()->user();
		$profile_details = SchoolProfile::where(['id'=>$user_data->school_profile_id])->first();//Fetch school profile details 

		$inserted_records=0;
        //Process each and every row ,insert all data in db
        foreach ($data as $row) {
              
            $check_exists = UserParents::where('mobile_number',$row['father_mobile_number']);
            $result = $check_exists->first(); //To check given subject name is already exists in DB.
            
        	$class_config_id = null;

        	if($upload_type == 'import')
        	{
                $class_id = AcademicClasses::where('class_name',$row['class_name'])->pluck('id')->first();
                $section_id = AcademicSections::where('section_name',$row['section_name'])->pluck('id')->first();
        		if($class_id!='' && $section_id!='')
                $class_config_id = AcademicClassConfiguration::where(['class_id'=>$class_id,'section_id'=>$section_id])->pluck('id')->first();
        	}
        	else
        		$class_config_id = $row['class_section'];


        	if(isset($row['gender']) && strtolower($row['gender']) == 'male')
        		$gender = 1;
        	else if(isset($row['gender']) && strtolower($row['gender']) == 'female')
        		$gender = 2;
        	else
        		$gender = 3;

        	if(isset($row['student_id']) && $row['student_id']!='')//arrange student details in array 
        		$student_details = UserStudents::where(['id'=>$row['student_id']])->first();
        	else
                $student_details = new UserStudents;

            $student_details->first_name= $row['student_name'];
            $student_details->admission_number=$row['admission_no'];
            $student_details->roll_number=isset($row['roll_no'])?$row['roll_no']:'';
            // $student_details->profile_image=public_path(env('SAMPLE_CONFIG_URL').'students/'.$image);
            $student_details->gender=$gender;
            $student_details->class_config=$class_config_id;
            $student_details->user_status=(isset($row['temporary_student']) && $row['temporary_student']!='' && strtolower($row['temporary_student'])=='yes')?5:1;
            $student_details->dob=date('Y-m-d',strtotime($row['dob']));
            $student_details->created_by=$userall_id;
        	$student_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
            $student_details->save();

   			$student_id = $student_details->id; // student id

   			if(isset($row['student_id']) && $row['student_id']!='')//arrange student details in array              
   			{
   				$page = 'create';
            	$userstudent_id = $student_details->user_id;
   			}
            else
            {
            	$page = 'edit';
            	 // generate and update staff id in db 
                $userstudent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'S'.sprintf("%04s", $student_id);
                $student_details->user_id = $userstudent_id;
                $student_details->save();                  
                
            }


            if((isset($row['father_mobile_number']) || isset($row['father_email_address'])) && ($row['father_mobile_number']!='' || $row['father_email_address']!=''))
            {
            	$check_exists = UserParents::where('mobile_number',$row['father_mobile_number'])->first(); //To check given subject name is already exists in DB.
            	if(!empty($check_exists))
            	{
            		$check_exists_mapping = UserStudentsMapping::where('student',$student_id)->where('parent',$check_exists->id)->pluck('id')->first();
            		if($check_exists_mapping == '')
            		{
   	            		$student_map = new UserStudentsMapping;
	                    $student_map->student = $student_id;  
	                    $student_map->parent = $check_exists->id;
	                    $student_map->created_by = $userall_id;
	                    $student_map->created_time=Carbon::now()->timezone('Asia/Kolkata');
	                    $student_map->save();
            		}
            	}
            	else
            	{
                    $parent=[];
                    if(isset($row['father_id']) && $row['father_id']!='')
                    	$parent_details = $parent = UserParents::where(['id'=>$row['father_id']])->first();
                	if(empty($parent))
                	{
                    	$parent_details = new UserParents;
                    	$parent_details->created_by=$userall_id;
                    	$parent_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
                	}
                	else
                	{
                		$parent_details->updated_by=$userall_id;
                    	$parent_details->updated_time=Carbon::now()->timezone('Asia/Kolkata');
                    	$userparent_id = $parent_details->user_id;
                	}
                    $parent_details->mobile_number= $row['father_mobile_number'];
                    $parent_details->first_name= $row['father_name'];
                    $parent_details->email_id= $row['father_email_address'];
                    $parent_details->user_status=1;//active
                    $parent_details->user_category = 1;
                    
                    
                    $parent_details->save();

                    $parent_id = $parent_details->id;

                    if(empty($parent))
                    {
	                    // generate and update staff id in db 
	                    $userparent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'P'.sprintf("%04s", $parent_id);
	                    $parent_details->user_id = $userparent_id;
	                    $parent_details->save(); 

	                    if($page == 'edit')
	                    {
		                    $user_all = new UserAll;
			                $user_all->user_table_id=$parent_id;
			                $user_all->user_role=Config::get('app.Parent_role');
			                $user_all->save(); 
	                    }

	                    $check_exists_mapping = UserStudentsMapping::where('student',$student_id)->where('parent',$parent_id)->pluck('id')->first();
	            		if($check_exists_mapping == '')
	            		{
		                    $student_map = new UserStudentsMapping;
		                    $student_map->student = $student_id;  
		                    $student_map->parent = $parent_id;
		                    $student_map->created_by = $userall_id;
		                    $student_map->created_time=Carbon::now()->timezone('Asia/Kolkata');
		                    $student_map->save();
		                }
	                }
	                $schoolusers = SchoolUsers::where(['user_id'=>$userparent_id,'school_profile_id'=>$user_data->school_profile_id])->first();

                	if(empty($schoolusers))
                    	$schoolusers = new SchoolUsers;

                    $schoolusers->school_profile_id=$user_data->school_profile_id;
                    $schoolusers->user_id=$userparent_id;
                    $schoolusers->user_mobile_number=$row['father_mobile_number'];
                    $schoolusers->user_password=bcrypt(date('dmY',strtotime($row['dob'])));
                    $schoolusers->user_email_id=$row['father_email_address'];
                    $schoolusers->user_role=Config::get('app.Parent_role');
                    $schoolusers->user_status=1;
                    $schoolusers->save();
            	}
            }
            $inserted_records++;          
        }

        if($inserted_records>0) //check empty array
            Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['students'=>1,'map_students'=>1]);
	}
    

	public static function upload_config_school($data,$userall_id,$upload_type)
	{
		$user_data = auth()->user();
		$profile_details = SchoolProfile::where(['id'=>$user_data->school_profile_id])->first();//Fetch school profile details 

		foreach ($data as $row) {
			if((isset($row['father_mobile_number']) || isset($row['father_email_address'])) && ($row['father_mobile_number']!='' || $row['father_email_address']!=''))
	        {
	        	$userparent_id=UserParents::where('mobile_number',$row['father_mobile_number'])->pluck('user_id')->first();
				$schoolusers = SchoolUsers::where(['user_id'=>$userparent_id,'school_profile_id'=>$user_data->school_profile_id])->first();

		    	if(empty($schoolusers))
		        	$schoolusers = new SchoolUsers;

		        $schoolusers->school_profile_id=$user_data->school_profile_id;
		        $schoolusers->user_id=$userparent_id;
		        $schoolusers->user_mobile_number=$row['father_mobile_number'];
		        $schoolusers->user_password=bcrypt($row['father_mobile_number']);
		        $schoolusers->user_email_id=$row['father_email_address'];
		        $schoolusers->user_role=Config::get('app.Parent_role');
		        $schoolusers->user_status=1;
		        $schoolusers->save();
		    }
		    if((isset($row['mother_mobile_number']) || isset($row['mother_email_address'])) && ($row['mother_mobile_number']!='' || $row['mother_email_address']!=''))
	        {
	        	$userparent_id=UserParents::where('mobile_number',$row['mother_mobile_number'])->pluck('user_id')->first();
	        	$schoolusers = SchoolUsers::where(['user_id'=>$userparent_id,'school_profile_id'=>$user_data->school_profile_id])->first();

	        	if(empty($schoolusers))
	            	$schoolusers = new SchoolUsers;

	            $schoolusers->school_profile_id=$user_data->school_profile_id;
	            $schoolusers->user_id=$userparent_id;
	            $schoolusers->user_mobile_number=$row['mother_mobile_number'];
	            $schoolusers->user_password=bcrypt($row['mother_mobile_number']);
	            $schoolusers->user_email_id=$row['mother_email_address'];
	            $schoolusers->user_role=Config::get('app.Parent_role');
	            $schoolusers->user_status=1;
	            $schoolusers->save(); 
	        }
	    }

	}

	// To activate or create static groups
	public function activate_default_groups()
	{
		$user_data = auth()->user();
		$management_group = $admin_group = $staff_group = $parent_group = $teaching_staffs = $parent_class_group=[];

		if($user_data->user_role == 1)//check role and get current user id
            $user_admin = UserAdmin::where(['user_id'=>$user_data->user_id])->pluck('id')->first();

        $userall_id = UserAll::where(['user_table_id'=>$user_admin,'user_role'=>$user_data->user_role])->pluck('id')->first();

		$group_activated = Configurations::where('school_profile_id',$user_data->school_profile_id)->pluck('default_group_activated')->first();
		if($group_activated==0)
		{
			// Create Management only group
			$usergroups = new UserGroups;
	        $usergroups->group_name='Management Only';
	        $usergroups->group_description='This group only contains management users.';
	        $usergroups->group_action=1;//1-admin
	        $usergroups->group_status=1;//1-active
	        $usergroups->group_type=1;
	        $usergroups->created_by=$userall_id;
	        $usergroups->created_time=Carbon::now()->timezone('Asia/Kolkata');
	        $usergroups->save();

	        // Get inserted recored id
	        $usergroup_id = $usergroups->id;

	        // Fetch all managment users
			$management_users = UserManagements::select('id','user_id')->where('user_status',1)->get()->toArray();

			foreach ($management_users as $management_key => $management_value) {
				$management_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$management_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Management_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($management_group))
				UserGroupsMapping::insert($management_group);

			$management_group=[];
			// Create Whole School group - Starts
			$usergroups = new UserGroups;
	        $usergroups->group_name='Whole School';
	        $usergroups->group_description='This group contains all users in the school.';
			$usergroups->group_action=1;//1-admin
	        $usergroups->group_status=1;//1-active
	        $usergroups->group_type=1;
	        $usergroups->created_by=$userall_id;
	        $usergroups->created_time=Carbon::now()->timezone('Asia/Kolkata');
	        $usergroups->save();

	        // Get inserted recored id
	        $usergroup_id = $usergroups->id;

	        foreach ($management_users as $management_key => $management_value) {
				$management_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$management_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Management_role'),
				]);
			}

			// Insert group users in mapping table
			if(!empty($management_group))
				UserGroupsMapping::insert($management_group);
			$management_group=[];
	        // Fetch all admin users
			$admin_users = UserAdmin::select('id','user_id')->where('user_status',1)->get()->toArray();

			foreach ($admin_users as $admin_key => $admin_value) {
				$admin_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$admin_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Admin_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($admin_group))
				UserGroupsMapping::insert($admin_group);
			$admin_group=[];
			// Fetch all Staffs users
			$staff_users = UserStaffs::select('id','user_id')->where('user_status',1)->get()->toArray();

			foreach ($staff_users as $staff_key => $staff_value) {
				$staff_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$staff_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Staff_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($staff_group))
				UserGroupsMapping::insert($staff_group);
			$staff_group=[];
			// Fetch all Parent users
			$parent_users = UserParents::select('id','user_id')->where('user_status',1)->get()->toArray();
			foreach ($parent_users as $parent_key => $parent_value) {
				$parent_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$parent_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Parent_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($parent_group))
				UserGroupsMapping::insert($parent_group);
			$parent_group=[];
			// Create Whole School group - Ends


			// Create school internal communication group
			$usergroups = new UserGroups;
	        $usergroups->group_name='School Internal Communication';
	        $usergroups->group_description='This group contains all teaching staffs and non-teaching staffs.';
	        $usergroups->group_action=1;//1-admin
	        $usergroups->group_status=1;//1-active
	        $usergroups->group_type=1;
	        $usergroups->created_by=$userall_id;
	        $usergroups->created_time=Carbon::now()->timezone('Asia/Kolkata');
	        $usergroups->save();

	        // Get inserted recored id
	        $usergroup_id = $usergroups->id;

	        foreach ($management_users as $management_key => $management_value) {
				$management_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$management_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Management_role'),
				]);
			}

			// Insert group users in mapping table
			if(!empty($management_group))
				UserGroupsMapping::insert($management_group);
			$management_group=[];

	        // Fetch all admin users
			$admin_users = UserAdmin::select('id','user_id')->where('user_status',1)->get()->toArray();

			foreach ($admin_users as $admin_key => $admin_value) {
				$admin_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$admin_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Admin_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($admin_group))
				UserGroupsMapping::insert($admin_group);
			
			$admin_group=[];
			// Fetch all Staffs users
			$staff_users = UserStaffs::select('id','user_id')->where('user_status',1)->get()->toArray();

			foreach ($staff_users as $staff_key => $staff_value) {
				$staff_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$staff_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Staff_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($staff_group))
				UserGroupsMapping::insert($staff_group);

			$staff_group=[];
			// Create Academic Staff group
			$usergroups = new UserGroups;
	        $usergroups->group_name='Academic Staffs';
	        $usergroups->group_description='This group contains only teaching staffs.';
	        $usergroups->group_action=1;//1-admin
	        $usergroups->group_status=1;//1-active
	        $usergroups->group_type=1;
	        $usergroups->created_by=$userall_id;
	        $usergroups->created_time=Carbon::now()->timezone('Asia/Kolkata');
	        $usergroups->save();

	        // Get inserted recored id
	        $usergroup_id = $usergroups->id;

	        foreach ($management_users as $management_key => $management_value) {
				$management_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$management_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Management_role'),
				]);
			}

			// Insert group users in mapping table
			if(!empty($management_group))
				UserGroupsMapping::insert($management_group);
			$management_group=[];

	        /// Fetch all Staffs users
			$staff_users = UserStaffs::select('id','user_id')->where('user_category',Config::get('app.Teaching_staff'))->where('user_status',1)->get()->toArray();

			foreach ($staff_users as $staff_key => $staff_value) {
				$staff_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$staff_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Staff_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($staff_group))
				UserGroupsMapping::insert($staff_group);
			$staff_group=[];

			// Fetch all admin users
			$admin_users = UserAdmin::select('id','user_id')->where('user_status',1)->get()->toArray();

			foreach ($admin_users as $admin_key => $admin_value) {
				$admin_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$admin_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Admin_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($admin_group))
				UserGroupsMapping::insert($admin_group);
			
			$admin_group=[];

			// Create Non-Teaching Staff group
			$usergroups = new UserGroups;
	        $usergroups->group_name='Non-Teaching Staffs';
	        $usergroups->group_description='This group contains only non-teaching staffs.';
	        $usergroups->group_action=1;//1-admin
	        $usergroups->group_status=1;//1-active
	        $usergroups->group_type=1;
	        $usergroups->created_by=$userall_id;
	        $usergroups->created_time=Carbon::now()->timezone('Asia/Kolkata');
	        $usergroups->save();

	        // Get inserted recored id
	        $usergroup_id = $usergroups->id;

	        foreach ($management_users as $management_key => $management_value) {
				$management_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$management_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Management_role'),
				]);
			}

			// Insert group users in mapping table
			if(!empty($management_group))
				UserGroupsMapping::insert($management_group);
			$management_group=[];

	        /// Fetch all Staffs users
			$staff_users = UserStaffs::select('id','user_id')->where('user_category',Config::get('app.Non_teaching_staff'))->where('user_status',1)->get()->toArray();

			foreach ($staff_users as $staff_key => $staff_value) {
				$staff_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$staff_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Staff_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($staff_group))
				UserGroupsMapping::insert($staff_group);
			$staff_group=[];

			// Fetch all admin users
			$admin_users = UserAdmin::select('id','user_id')->where('user_status',1)->get()->toArray();

			foreach ($admin_users as $admin_key => $admin_value) {
				$admin_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$admin_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Admin_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($admin_group))
				UserGroupsMapping::insert($admin_group);
			
			$admin_group=[];

						/*Admin - Management group*/
			$usergroups = new UserGroups;
	        $usergroups->group_name='Admin-Management';
	        $usergroups->group_description='This group contains all admin and Management users.';
	        $usergroups->group_action=1;//1-admin
	        $usergroups->group_status=1;//1-active
	        $usergroups->group_type=1;
	        $usergroups->created_by=$userall_id;
	        $usergroups->created_time=Carbon::now()->timezone('Asia/Kolkata');
	        $usergroups->save();

	        // Get inserted recored id
	        $usergroup_id = $usergroups->id;

	        foreach ($management_users as $management_key => $management_value) {
				$management_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$management_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Management_role'),
				]);
			}

			// Insert group users in mapping table
			if(!empty($management_group))
				UserGroupsMapping::insert($management_group);
			$management_group=[];

			// Fetch all admin users
			$admin_users = UserAdmin::select('id','user_id')->where('user_status',1)->get()->toArray();

			foreach ($admin_users as $admin_key => $admin_value) {
				$admin_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$admin_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Admin_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($admin_group))
				UserGroupsMapping::insert($admin_group);
			
			$admin_group=[];
			/*Admin - Management group*/
			
			/*create class-sections groups -  starts*/
			$class_config = AcademicClassConfiguration::select('id','class_id','section_id','class_teacher')->get()->toArray();

			if(!empty($class_config))
			{
				$classes = array_column(AcademicClasses::select('id','class_name')->get()->toArray(),'class_name','id');
				$sections = array_column(AcademicSections::select('id','section_name')->get()->toArray(),'section_name','id');
				foreach ($class_config as $class_key => $class_value) {
					if(isset($classes[$class_value['class_id']]) && $classes[$class_value['class_id']]!='' && isset($sections[$class_value['section_id']]) && $sections[$class_value['section_id']]!='' )
					{
						$usergroups = new UserGroups;
				        $usergroups->group_name=$classes[$class_value['class_id']].' - '.$sections[$class_value['section_id']];
				        $usergroups->group_description='This group contains only parents in class '.$classes[$class_value['class_id']].' - '.$sections[$class_value['section_id']];
				        $usergroups->group_action=1;//1-admin
				        $usergroups->group_status=1;//1-active
				        $usergroups->group_type=2;
				        $usergroups->class_config=$class_value['id'];
				        $usergroups->created_by=$userall_id;
				        $usergroups->created_time=Carbon::now()->timezone('Asia/Kolkata');
				        $usergroups->save();

				        // Get inserted recored id
				        $usergroup_id = $usergroups->id;
				        if(isset($class_value['class_teacher']))
				        {
					        // Insert class teacher into group
					        $classteacher_group = ([
								'group_id'=>$usergroup_id,
								'user_table_id'=>$class_value['class_teacher'],
								'group_access'=>1,
								'user_role'=>Config::get('app.Staff_role'),
							]);
							// Insert group users in mapping table
							UserGroupsMapping::insert($classteacher_group);
							$classteacher_group=[];
						}

						 // Fetch all admin users
						$admin_users = UserAdmin::select('id','user_id')->where('user_status',1)->get()->toArray();

						foreach ($admin_users as $admin_key => $admin_value) {
							$admin_group[] = ([
								'group_id'=>$usergroup_id,
								'user_table_id'=>$admin_value['id'],
								'group_access'=>1,
								'user_role'=>Config::get('app.Admin_role'),
							]);
						}
						// Insert group users in mapping table
						if(!empty($admin_group))
							UserGroupsMapping::insert($admin_group);
						
						$admin_group=[];

						foreach ($management_users as $management_key => $management_value) {
							$management_group[] = ([
								'group_id'=>$usergroup_id,
								'user_table_id'=>$management_value['id'],
								'group_access'=>1,
								'user_role'=>Config::get('app.Management_role'),
							]);
						}

						// Insert group users in mapping table
						if(!empty($management_group))
							UserGroupsMapping::insert($management_group);
						$management_group=[];

				        // Fetch all Staffs users
						$teaching_staffs = AcademicSubjectsMapping::where('class_config',$class_value['id'])->pluck('staff')->toArray();

						if(!empty($teaching_staffs) && !empty($teaching_staffs[0]))
						{
							foreach ($teaching_staffs as $staff_key => $staff_value) {
								$teaching_staffs_group[] = ([
									'group_id'=>$usergroup_id,
									'user_table_id'=>$staff_value,
									'group_access'=>2,
									'user_role'=>Config::get('app.Staff_role'),
								]);
							}
							// Insert group users in mapping table
							if(!empty($teaching_staffs_group))
								UserGroupsMapping::insert($teaching_staffs_group);
							$teaching_staffs_group=[];
						}
						//Fetch all Parent users
						$student_ids = UserStudents::select('id')->where('class_config',$class_value['id'])->get()->toArray();

						if(!empty($student_ids))
						{
							$studentids = array_column($student_ids,'id');

							$parent_ids = UserStudentsMapping::whereIn('student',$studentids)->pluck('parent')->toArray();
							if(!empty($parent_ids))
							{
								$teaching_staffs_group = [];
								foreach ($parent_ids as $parentkey => $parentvalue) {
									$teaching_staffs_group[] = ([
										'group_id'=>$usergroup_id,
										'user_table_id'=>$parentvalue,
										'group_access'=>2,
										'user_role'=>Config::get('app.Parent_role'),
									]);
								}

								// Insert group users in mapping table
								if(!empty($teaching_staffs_group))
									UserGroupsMapping::insert($teaching_staffs_group);
								$teaching_staffs_group=[];
							}
						}
				    }
				}
			}
			/*create class-sections groups -  ends*/
			
			Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['default_group_activated'=>1]);

			return response()->json(['message'=>'Default Group Activated Successfully...']);
		}
		else
			return response()->json(['message'=>'Default Group Already Activated...']);
	}
	// Fetch group details
	public function user_group_list()
	{
		$user_data = auth()->user();
		$user_role = '';
		$school_name = SchoolProfile::where('id',$user_data->school_profile_id)->pluck('school_name')->first();
		if($user_data->user_role == Config::get('app.Admin_role'))//check role and get current user id
		{
            $userid = UserAdmin::where(['user_id'=>$user_data->user_id])->pluck('id')->first();
            $user_role = 'admin';
		}
        else if($user_data->user_role == Config::get('app.Management_role'))
        {
        	$userid = UserManagements::where(['user_id'=>$user_data->user_id])->pluck('id')->first();
        	$user_role = 'management';
        }
        else if($user_data->user_role == Config::get('app.Staff_role'))
        {
        	$userid = UserStaffs::where(['user_id'=>$user_data->user_id])->pluck('id')->first();
        	$user_role = 'staff';
        }
        else if($user_data->user_role == Config::get('app.Parent_role'))
        {
        	$userid = UserParents::where(['user_id'=>$user_data->user_id])->pluck('id')->first();
        	$user_role = 'parent';
        }

		$group_ids = UserGroupsMapping::select('group_id')->where('user_role',$user_data->user_role)->where('user_table_id',$userid)->groupBy('group_id')->get()->toArray();
		
		if(!empty($group_ids))
		{
			$groupids = array_column($group_ids,'group_id');
			$group_list = UserGroups::select('id','group_name','group_description')->where('group_type',1)->whereIn('id',$groupids)->get()->toArray(); //default groups
			return response()->json(compact('school_name','user_role','group_list'));
		}
		else
			return response()->json(['status'=>false,'message'=>'No groups Configured!...']);
	}

	public function classes_group(Request $request)
	{
		$user_data = auth()->user(); //check authentication
		$class_group=[];
		$school_name = SchoolProfile::where('id',$user_data->school_profile_id)->pluck('school_name')->first();//fetch school name
		$user_category = '';
		if($user_data->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $userid = UserAdmin::where(['user_id'=>$user_data->user_id])->pluck('id')->first();
        else if($user_data->user_role == Config::get('app.Management_role'))
        	$userid = UserManagements::where(['user_id'=>$user_data->user_id])->pluck('id')->first();
        else if($user_data->user_role == Config::get('app.Staff_role'))
        {
        	$data = UserStaffs::select('id','user_category')->where(['user_id'=>$user_data->user_id])->first();
        	$userid = $data['id'];
        	$user_category = ($data['user_category']!=null && $data['user_category']!='' && $data['user_category'] == Config::get('app.Teaching_staff'))?'teaching staff':'nonteaching staff';
        }
        else if($user_data->user_role == Config::get('app.Parent_role'))
        	$userid = UserParents::where(['user_id'=>$user_data->user_id])->pluck('id')->first();

        $group_ids = UserGroupsMapping::select('group_id','group_access')->where('user_role',$user_data->user_role)->where('user_table_id',$userid)->groupBy('group_id')->get()->toArray();

		if(!empty($group_ids)) //fetch school groups
		{
			$groupids = array_column($group_ids,'group_id');
			$groupaccess = array_column($group_ids,'group_access','group_id');
			$group_list = UserGroups::select('id','group_name','group_description','class_config')->where('group_type',2)->whereIn('id',$groupids);
			if($request->student_id!='')
			{
				$config_id = UserStudents::where('id',$request->student_id)->pluck('class_config')->first();
				$group_list = $group_list->where('class_config',$config_id);
			}
			$group_list = $group_list->get()->toArray(); //class groups
			foreach ($group_list as $group_key => $group_value) {
				$approval_pending = $class_approval_pending = $section_approval_pending = 0;
				$classteacher_name ='';
				$classteacher_id = AcademicClassConfiguration::where('id',$group_value['class_config'])->pluck('class_teacher')->first();
				if($classteacher_id!='')
					$classteacher_name = UserStaffs::where('id',$classteacher_id)->pluck('first_name')->first();
				$chat_count = $homework_count = 0;
				$chat_count = count(Communications::select('id')->where('group_id',$group_value['id'])->whereNull('approval_status')->where('communication_type',1)->where('distribution_type','!=',6)->where('distribution_type','!=',8)->get()->toArray());
				$homework_count = count(Communications::select('id')->where('group_id',$group_value['id'])->whereNull('approval_status')->where('communication_type',2)->where('actioned_time','>=',date("Y-m-d",strtotime(Carbon::now()->timezone('Asia/Kolkata'))))->get()->toArray());
				$approval_pending = $chat_count+$homework_count;

				$class_approval_pending = count(Communications::where('group_id',2)->Where('visible_to', 'like', '%' .$group_value['class_config']. ',%')->where('communication_type',1)->where('distribution_type',8)->whereNull('approval_status')->pluck('id')->toArray());

				$section_approval_pending = count(Communications::where('group_id',2)->Where('visible_to', 'like', '%' .$group_value['class_config']. ',%')->where('communication_type',1)->where('distribution_type',6)->whereNull('approval_status')->pluck('id')->toArray());

				$approval_pending+=$class_approval_pending+$section_approval_pending;

				$parent_ids = UserGroupsMapping::where(['user_role'=>Config::get('app.Parent_role'),'group_id'=>$group_value['id']])->pluck('user_table_id')->toArray();
				$all_user_count = UserGroupsMapping::where(['group_id'=>$group_value['id']])->pluck('user_table_id')->toArray();
				$parent_online = 0;
				if(!empty($parent_ids))
				{
					$parent_user_ids = UserParents::whereIn('id',$parent_ids)->pluck('user_id')->toArray();
					$last_login_details = SchoolUsers::whereIn('user_id',$parent_user_ids)->whereDate('last_login', Carbon::today())->pluck('last_login')->toArray();
					if(!empty($last_login_details))
					{
						foreach ($last_login_details as $loginkey => $loginvalue) {

    						$newDate = strtotime(Carbon::now()->timezone('Asia/Kolkata').'-1 minutes');
							$last_login_time = strtotime($loginvalue);
							if($last_login_time>=$newDate)
							{
								$parent_online++;
							}
						}
					}
				}
				$subject_list = [];
				$get_subject_ids = AcademicSubjectsMapping::select('subject')->where('class_config',$group_value['class_config'])->get()->toArray(); //fetch all the subject ids for that corresponding class
				if(!empty($get_subject_ids))
				{
					$subject_list = AcademicSubjects::select('id','subject_name')->whereIn('id',$get_subject_ids)->get()->toArray(); //fetch all subject names from ids
					$homework_date = date("Y-m-d");
					if($request->homework_date!='')
						$homework_date = $request->homework_date;
					$approved_subject_homeworks = Communications::whereIn('subject_id',$get_subject_ids)->where('communication_type',2)->where('group_id',$group_value['id'])->where('actioned_time', 'like', '%' .$homework_date. '%')->get()->toArray();
				}

				if($groupaccess[$group_value['id']] == 1)
				{
					$classteacher_group[$group_key]['group_id'] = $group_value['id'];
					$classteacher_group[$group_key]['group_name'] = $group_value['group_name'];
					$classteacher_group[$group_key]['group_description'] = $group_value['group_description'];
					$classteacher_group[$group_key]['class_teacher'] = $classteacher_name;
					$classteacher_group[$group_key]['approval_pending'] = $approval_pending;
					$classteacher_group[$group_key]['parent_online'] = $parent_online;
					$classteacher_group[$group_key]['class_config'] = $group_value['class_config'];
					$classteacher_group[$group_key]['classteacher']=($groupaccess[$group_value['id']] == 1)?'yes':'no';
					$classteacher_group[$group_key]['total_parent_count'] =(!empty($parent_ids))?count($parent_ids):0;
					$classteacher_group[$group_key]['all_user_count'] =(!empty($all_user_count))?count($all_user_count):0;
					$classteacher_group[$group_key]['subject_list']= $subject_list;
					$classteacher_group[$group_key]['uploaded_homeworks_count']=(!empty($approved_subject_homeworks))?count($approved_subject_homeworks):0;


				}
				else
				{
					$staff_group[$group_key]['group_id'] = $group_value['id'];
					$staff_group[$group_key]['group_name'] = $group_value['group_name'];
					$staff_group[$group_key]['group_description'] = $group_value['group_description'];
					$staff_group[$group_key]['class_teacher'] = $classteacher_name;
					$staff_group[$group_key]['approval_pending'] = $approval_pending;
					$staff_group[$group_key]['parent_online'] = $parent_online;
					$staff_group[$group_key]['class_config'] = $group_value['class_config'];
					$staff_group[$group_key]['classteacher']=($groupaccess[$group_value['id']] == 1)?'yes':'no';
					$staff_group[$group_key]['total_parent_count'] =(!empty($parent_ids))?count($parent_ids):0;
					$staff_group[$group_key]['all_user_count'] =(!empty($all_user_count))?count($all_user_count):0;
					$staff_group[$group_key]['subject_list']= $subject_list;
					$staff_group[$group_key]['uploaded_homeworks_count']=(!empty($approved_subject_homeworks))?count($approved_subject_homeworks):0;
				}
			}
			if(!empty($classteacher_group))
				$class_group =  array_merge($class_group,$classteacher_group);

			if(!empty($staff_group))
				$class_group =  array_merge($class_group,$staff_group);

			return response()->json(compact('school_name','user_category','class_group'));
		}
		else
			return response()->json(['status'=>false,'message'=>'No groups Configured!...']);
	}

	// Configurations tags
	public function configuration_tags()
	{
		// Save last login in DB
        $user = auth()->user();

		// Fetch configuration details from DB for corresponding school
        $configurations = Configurations::where('school_profile_id',$user->school_profile_id)->first();

        // configuration details
        $configuration = ([
        	'divisions'=>($configurations->division==1)?true:false,
            'sections'=>($configurations->sections==1)?true:false,
            'classes'=>($configurations->classes==1)?true:false,
            'map_classes_sections'=>($configurations->map_classes_sections==1)?true:false,
            'subjects'=>($configurations->subjects==1)?true:false,
            'map_subjects'=>($configurations->map_subjects==1)?true:false,
            'staffs'=>($configurations->staffs==1)?true:false,
            'map_staffs'=>($configurations->map_staffs==1)?true:false,
            'management'=>($configurations->management==1)?true:false,
            'students'=>($configurations->students==1)?true:false,
        ]);
        // return token 
        return response()->json(compact('configuration'));
	}

	// send welcome message to all users
	public function send_welcome_message(Request $request)
	{
		// authenticate the user
		$user = auth()->user();

		//get input of default password type and store it in school profile table for future purpose.
		if($request->default_password_type!='')
		{
			$default_password_type = strtolower($request->default_password_type);
			SchoolProfile::where('id',$user->school_profile_id)->update(['default_password_type'=>$default_password_type]);
		}
		else
		{
			$default_password_type = SchoolProfile::where('id',$user->school_profile_id)->pluck('default_password_type')->first();
			if($default_password_type == '')
				$default_password_type = 'mobile_number';
		}

		// fetch all users mobile number under role staff,parent and management
		$userslist = SchoolUsers::whereIn('user_role',[3])->where('school_profile_id',$user->school_profile_id)->get()->toArray();
		// get the common id to insert
		if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->first();
        else if($user->user_role == Config::get('app.Management_role'))
            $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->first();
        else if($user->user_role == Config::get('app.Staff_role'))
            $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->first();
        else if($user->user_role == Config::get('app.Parent_role'))
            $user_table_id = UserParents::where(['user_id'=>$user->user_id])->first();//fetch id from user all table to store notification triggered user
        $userall_id = UserAll::where(['user_table_id'=>$user_table_id->id,'user_role'=>$user->user_role])->pluck('id')->first();
        // fetch welcome template
		$templates = Smstemplates::whereRaw('LOWER(`label_name`) LIKE ? ',['%'.trim(strtolower("welcome_message")).'%'])->where('status',1)->first();
        if(!empty($templates) && !empty($userslist)) //check empty condition
        {
        	// remove duplicate mobile numbers;
        	// $userslist = array_unique($userslist);

        	// run the loop and trigger the sms to all users one by one.
  	      	foreach ($userslist as $key => $value) {
  	      		if($value['user_role'] == 3)
  	      		{
	      			$schoolusers = SchoolUsers::where(['user_role'=>$value['user_role'],'school_profile_id'=>$user->school_profile_id,'id'=>$value['id']]);
	      			if($default_password_type == 'admission_number' || $default_password_type == 'dob')
	      			{
	      				$parent_id = UserParents::where('user_id',$value['user_id'])->pluck('id')->first();
                        $mapped_student = UserStudentsMapping::where('parent',$parent_id)->pluck('student')->first();
	      				$student_details = UserStudents::where('id',$mapped_student)->first();
	      			}

	      			if($default_password_type == 'mobile_number')
	      			{
	      				$password = $value['user_mobile_number'];
	      				$schoolusers = $schoolusers->update(['user_password'=>bcrypt($value['user_mobile_number'])]);
	      			}
	      			else if($default_password_type == 'admission_number')
	      			{
	      				$password = $student_details->admission_number;
	      				$schoolusers = $schoolusers->update(['user_password'=>bcrypt($student_details->admission_number)]);
	      			}
	      			else if($default_password_type == 'dob')
	      			{
	      				$password = date('dmY',strtotime($student_details->dob));
	      				$schoolusers = $schoolusers->update(['user_password'=>bcrypt($password)]);
	      			}
	      		}
	      		else
	      			$password =$value['user_mobile_number'];


	      			// replace the mobile and password with corresponding value
            	$message = str_replace("*mobileno*",$value['user_mobile_number'],$templates->message);
            	$message = str_replace("*password*",$password,$message);
            	// echo $message;exit;
            	// call send sms function
			    $delivery_details = APISmsController::SendSMS($value['user_mobile_number'],$message,$templates->dlt_template_id);

			    // store log in db.
			    $status = 0;
			    if(!empty($delivery_details) && isset($delivery_details['status']) && $delivery_details['status'] == 1)
			    	$status = 1;
            	$smslogs = ([
            		'sms_description'=>$message,
            		'sms_count'=>1,
            		'mobile_number'=>$value['user_mobile_number'],
            		'sent_by'=>$userall_id,
            		'status'=>$status
            	]);
            	if(!empty($smslogs))
        			Smslogs::insert($smslogs); // store log in db.
        	}
        	
        	return (['status'=>true,'message'=>'SMS sent Successfully!...']);
        }
        else
            return (['status'=>false,'message'=>'Please configure template details!...']);
	}

	// Change mobile number
    public function change_mobile_number(Request $request)
    {
        // Get authorizated user details
        $user = auth()->user();

        if($user->user_role == Config::get('app.Management_role'))
	        $mobile_number = UserManagements::where(['mobile_number'=>$request->mobile_number])->pluck('id')->first();
	    else if($user->user_role == Config::get('app.Staff_role'))
	    	$mobile_number = UserStaffs::where(['mobile_number'=>$request->mobile_number])->pluck('id')->first();
	    else if($user->user_role == Config::get('app.Parent_role'))
	    	$mobile_number = UserParents::where(['mobile_number'=>$request->mobile_number])->pluck('id')->first();

	    if($mobile_number!='')
	    	return (['status'=>false,'message'=>'Given Mobile no already exists!.']);
        if(!isset($request->pin) || $request->pin=='')
        {
        	$digits = 4;
	        $otp = rand(pow(10, $digits-1), pow(10, $digits)-1); //generate 4 digit otp
	        app('App\Http\Controllers\APILoginController')->saveOtp($user, $otp); //save OTP in DB
	        $user->user_mobile_number = $request->mobile_number;
	        $mail_response = (($user->user_email_id != null)||($user->user_email_id != ''))?app('App\Http\Controllers\APILoginController')->sendMail($user, $otp):'Email not available'; //Send OTP to email
	        $mobile_response = (($request->mobile_number != null)||($request->mobile_number!= ''))?app('App\Http\Controllers\APILoginController')->sendMessage($user, $otp):'Mobile number not available'; //Send OTP to user mobile no
            return response()->json(['status'=>true,'message'=>$mobile_response]);
        }
        else
        {
        	$otp_exp_time = strtotime("+15 minutes",strtotime($user->otp_gen_time));
        	$current_time = strtotime(Carbon::now()->timezone('Asia/Kolkata'));

        	if(strtotime($user->otp_gen_time) < $current_time && $otp_exp_time > $current_time) {
            	if($user->login_otp == $request->pin) {
            		app('App\Http\Controllers\APILoginController')->saveOtpAsNull($user);
            		$user->user_mobile_number = $request->mobile_number;
                    $user->save();
			        //Update lastest mobile number in school users table.
			        $user_table_id = app('App\Http\Controllers\APILoginController')->get_user_table_id($user);

			        $userall_id = UserAll::where(['user_table_id'=>$user_table_id->id,'user_role'=>$user->user_role])->pluck('id')->first();//fetch id from user all table to store notification triggered user
			        if($user->user_role == Config::get('app.Management_role'))
			            $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->update(['mobile_number'=>$request->mobile_number,'updated_by'=>$userall_id,'updated_time'=>Carbon::now()->timezone('Asia/Kolkata')]);
			        else if($user->user_role == Config::get('app.Staff_role'))
			            $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->update(['mobile_number'=>$request->mobile_number,'updated_by'=>$userall_id,'updated_time'=>Carbon::now()->timezone('Asia/Kolkata')]);
			        else if($user->user_role == Config::get('app.Parent_role'))
			            $user_table_id = UserParents::where(['user_id'=>$user->user_id])->update(['mobile_number'=>$request->mobile_number,'updated_by'=>$userall_id,'updated_time'=>Carbon::now()->timezone('Asia/Kolkata')]);
		       	
		        	return response()->json(['status'=>true,'message'=>'Mobile number Updated Successfully!...']);
		        }
		        else
       			   	return response()->json(['status'=>false,'message'=>'Entered OTP does not matched']);
        	}
        	else {
            	app('App\Http\Controllers\APILoginController')->saveOtpAsNull($user);
            	return response()->json(['status'=>false,'message'=>'OTP is expired']);
        	}
        }

    }

    // Store parent details (Staff as parent)
    public function staff_as_parent(Request $request)
    {
    	// Get authorizated user details
        $user = auth()->user();

        $parent_mobile_numbers = explode(',', $request->parent_mobile_number); //split the if more than one number is given
        if(!empty($parent_mobile_numbers))
        {
        	$parent_ids = UserParents::whereIn('mobile_number',$parent_mobile_numbers)->pluck('id')->toArray();//fetch parent id based on mobile number
        	if(!empty($parent_ids))
        	{
        		$parent = implode(',',$parent_ids);
        		UserStaffs::where('user_id',$user->user_id)->update(['child_same_school'=>'Y','parent_id'=>$parent]);//store student id in staff details
        		return response()->json(['status'=>true,'message'=>'Store Successfully!...']);
        	}
        }
        else
        	return response()->json(['status'=>false,'message'=>'Please enter a mobile numbers']);
    }

    // View Stored parent details (Staff as parent)
    public function view_staff_as_parent()
    {
    	// Get authorizated user details
        $user = auth()->user();

        $staff_details = UserStaffs::select('child_same_school','parent_id')->where('user_id',$user->user_id)->first();

        if(!empty($staff_details))
        {
        	if($staff_details->child_same_school == 'Y')
        	{
	        	$parent_id = explode(',', $staff_details->parent_id); //split the if more than one number is given
	        	$parent_mobile_numbers = UserParents::whereIn('id',$parent_id)->pluck('mobile_number')->toArray();//fetch parent id based on mobile number
	        	if(!empty($parent_mobile_numbers))
	        	{
	        		$parents = implode(',',$parent_mobile_numbers);
	        		//return parent mobile numbers in staff details
	        		return response()->json(['status'=>true,'staff_as_parent'=>(['child_same_school'=>$staff_details->child_same_school,'parent_mobile_number'=>$parents])]);
	        	}
        	}
        	else
        		return response()->json(['status'=>true,'staff_as_parent'=>(['child_same_school'=>$staff_details->child_same_school,'parent_mobile_number'=>''])]);
        }
        else
        	return response()->json(['status'=>false,'message'=>'Please enter a mobile numbers']);
    }

    // Total Userss count 
    public function users_count()
    {
    	// Get authorizated user details
        $user = auth()->user();
        $total_users = $student = $parent = $teaching_staffs = $non_teaching_staffs = $management = $total_father = $total_mother = $total_guardian = $total_installed_guardian = $total_installed_father = $total_installed_mother = $inactive_user = $total_management = $total_admin = $total_teaching_staff = $total_nonteaching_staff = $total_installed_admin = $total_installed_management = $total_installed_teaching = $total_installed_nonteaching = $inactive_user = 0 ;
 
        // fetch all the users under role staff,parent and management
		$userslist = SchoolUsers::select(DB::raw('count(*) as count'),'user_role')->whereIn('user_role',[2,3,5])->where('school_profile_id',$user->school_profile_id)->where('user_status',1)->groupBy('user_role')->get()->toArray();

		$deactiveusercount = SchoolUsers::select(DB::raw('count(*) as count'))->where('school_profile_id',$user->school_profile_id)->where('user_status',2)->first();
		$school_name = SchoolProfile::where('id',$user->school_profile_id)->pluck('school_name')->first();

		// check if values or exists or not
		if(!empty($userslist))
		{
			$separate_count = array_column($userslist,'count','user_role'); //apply array column function to get count and role
			$total_users = array_sum($separate_count); //sum the count
			$management = isset
			($separate_count[Config::get('app.Management_role')])?$separate_count[Config::get('app.Management_role')]:0; //management users count
			$parent = isset
			($separate_count[Config::get('app.Parent_role')])?$separate_count[Config::get('app.Parent_role')]:0; // parent users count
		}

		// staff count based on category
		$stafflist = UserStaffs::select(DB::raw('count(*) as count'),'user_category')->where('user_status',1)->groupBy('user_category')->get()->toArray();

		// // check if values or exists or not
		// if(!empty($stafflist))
		// {
		// 	$separate_staff_count = array_column($stafflist,'count','user_category'); //apply array column function to get count and category
		// 	$teaching_staffs = $separate_staff_count[Config::get('app.Teaching_staff')]; //teaching staff count
		// 	$non_teaching_staffs = $separate_staff_count[Config::get('app.Non_teaching_staff')]; // non teaching staff count
		// }

		// student count
		$student = UserStudents::select(DB::raw('count(*) as count'))->where('user_status',1)->pluck('count')->first();

		// father installed count
		$total_father_count = UserParents::where('user_status',1)->where('user_category',Config::get('app.Father'))->get()->toArray();
		if(!empty($total_father_count))
		{
			$father_common_ids = UserAll::where('user_role',Config::get('app.Parent_role'))->whereIn('user_table_id',array_column($total_father_count,'id'))->pluck('id')->toArray();//get common id's for all the users
			if(!empty($father_common_ids))
			{
				$total_father = count($father_common_ids);
				$total_installed_father = AppUsers::select(DB::raw('count(*) as count'))->whereIn('loginid',$father_common_ids)->pluck('count')->first();
			}
		}

		// mother installed count
		$total_mother_count = UserParents::where('user_status',1)->where('user_category',Config::get('app.Mother'))->get()->toArray();
		if(!empty($total_mother_count))
		{
			$mother_common_ids = UserAll::where('user_role',Config::get('app.Parent_role'))->whereIn('user_table_id',array_column($total_mother_count,'id'))->pluck('id')->toArray();//get common id's for all the users
			if(!empty($mother_common_ids))
			{
				$total_mother = count($mother_common_ids);
				$total_installed_mother = AppUsers::select(DB::raw('count(*) as count'))->whereIn('loginid',$mother_common_ids)->pluck('count')->first();
			}
		}

		// guardian installed count
		$total_guardian_count = UserParents::where('user_status',1)->where('user_category',Config::get('app.Guardian'))->get()->toArray();
		if(!empty($total_guardian_count))
		{
			$guardian_common_ids = UserAll::where('user_role',Config::get('app.Parent_role'))->whereIn('user_table_id',array_column($total_guardian_count,'id'))->pluck('id')->toArray();//get common id's for all the users
			if(count($guardian_common_ids)>0)
			{
				$total_guardian = count($guardian_common_ids);
				$total_installed_guardian = AppUsers::select(DB::raw('count(*) as count'))->whereIn('loginid',$guardian_common_ids)->pluck('count')->first();
			}
		}

		// admin installed count
		$total_admin_count = UserAdmin::where('user_status',1)->get()->toArray();
		if(!empty($total_admin_count))
		{
			$admin_common_ids = UserAll::where('user_role',Config::get('app.Admin_role'))->pluck('id')->toArray();//get common id's for all the users
			$total_admin = count($admin_common_ids);
			$total_installed_admin= AppUsers::select(DB::raw('count(*) as count'))->whereIn('loginid',$admin_common_ids)->pluck('count')->first();
		}

		// management installed count
		$total_management_count = UserManagements::where('user_status',1)->get()->toArray();
		if(!empty($total_management_count))
		{
			$management_common_ids = UserAll::where('user_role',Config::get('app.Management_role'))->pluck('id')->toArray();//get common id's for all the users
			$total_management = count($management_common_ids);
			$total_installed_management= AppUsers::select(DB::raw('count(*) as count'))->whereIn('loginid',$management_common_ids)->pluck('count')->first();
		}

		// teaching staff installed count
		$total_teaching_count = UserStaffs::where('user_status',1)->where('user_category',3)->get()->toArray();
		if(!empty($total_teaching_count))
		{
			$teaching_staff_common_ids = UserAll::where('user_role',Config::get('app.Staff_role'))->whereIn('user_table_id',array_column($total_teaching_count,'id'))->pluck('id')->toArray();//get common id's for all the users
			$total_teaching_staff = count($teaching_staff_common_ids);
			$total_installed_teaching = AppUsers::select(DB::raw('count(*) as count'))->whereIn('loginid',$teaching_staff_common_ids)->pluck('count')->first();
		}

		// teaching staff installed count
		$total_non_teaching_count = UserStaffs::where('user_status',1)->where('user_category',4)->get()->toArray();
		if(!empty($total_non_teaching_count))
		{
			$nonteaching_staff_common_ids = UserAll::where('user_role',Config::get('app.Staff_role'))->whereIn('user_table_id',array_column($total_non_teaching_count,'id'))->pluck('id')->toArray();//get common id's for all the users
			$total_nonteaching_staff = count($nonteaching_staff_common_ids);
			$total_installed_nonteaching = AppUsers::select(DB::raw('count(*) as count'))->whereIn('loginid',$nonteaching_staff_common_ids)->pluck('count')->first();
		}


		$last_week = date('Y-m-d', strtotime('-7 days'));
		$inactive_user = SchoolUsers::select(DB::raw('count(*) as count'))->where('last_login', '<=', $last_week)->pluck('count')->first();

        $users_count = ([
        	'school_name'=>$school_name,
        	'deactiveusercount'=>$deactiveusercount['count'],
        	'total_users'=>$total_users+$student,
        	'student'=>$student,
        	'parent'=>$parent,
        	// 'teaching_staffs'=>$teaching_staffs,
        	// 'non_teaching_staffs'=>$non_teaching_staffs,
        	'management'=>$management,
        	'total_father'=>$total_father,
        	'total_mother'=>$total_mother,
        	'total_guardian'=>$total_guardian,
        	'total_admin'=>$total_admin,
        	'total_management'=>$total_management,
        	'total_teaching_staff'=>$total_teaching_staff,
        	'total_non_teaching_staff'=>$total_nonteaching_staff,
        	'total_installed_father'=>$total_installed_father,
        	'total_installed_mother'=>$total_installed_mother,
        	'total_installed_guardian'=>$total_installed_guardian,
        	'total_installed_admin'=>$total_installed_admin,
        	'total_installed_management'=>$total_installed_management,
        	'total_installed_teaching'=>$total_installed_teaching,
        	'total_installed_nonteaching'=>$total_installed_nonteaching,
        	'inactive_user'=>$inactive_user,
        ]);

        return response()->json($users_count);
    }


    public function importdob(Request $request)
    {
    	Excel::import(new DOBImport,request()->file('import_file'));
    	return response()->json(['status'=>true,'message'=>'Updated Successfully!...']);
    }

    // All student list
	public function all_student_list(Request $request)
	{
		// Save last login in DB
        $user = auth()->user();
        $member_student_list = [];
        $student_list = UserStudents::select('*');
        if(isset($request->search) && $request->search!='')
            $student_list = $student_list->where('first_name', 'like', '%' . $request->search . '%')->orWhere('mobile_number', 'like', '%' . $request->search . '%');
        	
        $student_list =$student_list->orderBy('class_config')->get()->toArray();

       	// $currentPage = LengthAwarePaginator::resolveCurrentPage(); // Get current page form url e.x. &page=1
        $currentPage = $request->page;
        $itemCollection = new Collection($student_list); // Create a new Laravel collection from the array data
        $perPage = 10;
        // Slice the collection to get the items to display in current page
        $sortedCollection = $itemCollection->sortBy('class_config');
        $currentPageItems = $sortedCollection->values()->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems= new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);

        $paginatedItems->setPath($request->url()); // set url path for generted links
        $paginatedItems->appends($request->page);

        $tempdata = $paginatedItems->toArray(); 
        $member_student_list['total'] = $tempdata['total'];
        $member_student_list['per_page'] = $tempdata['per_page'];
        $member_student_list['current_page'] = $tempdata['current_page'];
        $member_student_list['last_page'] = $tempdata['last_page'];
        $member_student_list['next_page_url'] = $tempdata['next_page_url'];
        $member_student_list['prev_page_url'] = $tempdata['prev_page_url'];
        $member_student_list['from'] = $tempdata['from'];
        $member_student_list['to'] = $tempdata['to'];
       	$index = 0; 
        $list = ($currentPage <= 0)?$student_list:$tempdata['data'];
        	
        foreach ($list as $key => $value) {
        	
        	$classessections =[];
        	if(isset($value['class_config']))
        		$classessections = AcademicClassConfiguration::select('id','class_id','section_id','division_id','class_teacher')->where('id',$value['class_config'])->first();
        	
        	$member_student_list['data'][$index]=([
        		'id'=>$value['id'],
        		'user_id'=>$value['user_id'],
	        	'first_name' => $value['first_name'],
	        	'last_name' => $value['last_name'],
        		'roll_number'=>$value['roll_number'],
        		'gender'=>$value['gender'],
        		'class_config'=>$value['class_config'],
        		'created_by'=>$value['created_by'],
        		'updated_by'=>$value['updated_by'],
        		'created_time'=>$value['created_time'],
        		'updated_time'=>$value['updated_time'],
        		'user_status'=>$value['user_status'],
	        	'father_name' => '',
	        	'mother_name' => '',
	        	'guardian_name'=>'',
	        	'father_mobile' => 0,
		        'mother_mobile'=>0,
		        'guardian_mobile' => 0,
	        	'student_name' => $value['first_name'],
        		// $parent_list[$key]['mobile_number'] = $value['mobile_number'];
	        	'dob' => (isset($value['dob']))?$value['dob']:'',
	        	'admission_number' => (isset($value['admission_number']))?$value['admission_number']:'',
        		'class' => (!empty($classessections))?$classessections->classsectionName():'',
        		'class_teacher' => (!empty($classessections))?UserStaffs::where('id',$classessections->class_teacher)->pluck('first_name')->first():'',
        		'profile_image' => $value['profile_image'],
        		'student_profile_image' => (isset($value['profile_image']))?$value['profile_image']:'',
        	]);
        	$parent_id = UserStudentsMapping::where('student',$value['id'])->pluck('parent')->toArray();
        	foreach($parent_id as $parentid)
        	{

            	$parent_details = UserParents::whereIn('id',$parent_id)->first();
            	if(!empty($parent_details))
            	{
		        	if($parent_details->user_category == 1)
		        	{
		        		$member_student_list['data'][$index]['father_name'] = $parent_details->first_name;
		        		$member_student_list['data'][$index]['father_mobile'] = $parent_details->mobile_number;

		        	}
		        	else if($parent_details->user_category == 2)
		        	{
		        		$member_student_list['data'][$index]['mother_name'] = $parent_details->first_name;
		        		$member_student_list['data'][$index]['mother_mobile'] = $parent_details->mobile_number;

		        	}
		        	else if($parent_details->user_category == 9)
		        	{
		        		$member_student_list['data'][$index]['guardian_name'] = $parent_details->first_name;
		        		$member_student_list['data'][$index]['guardian_mobile'] = $parent_details->mobile_number;

		        	}
		        }
        	}
        	$index++;
        }
        // if($currentPage <= 0)
        // 	$member_student_list = $member_student_list['data'];
        return response()->json($member_student_list);
	}
	
	// All admin list
	public function all_admin_list(Request $request)
	{
		// Save last login in DB
        $user = auth()->user();
        $member_admin_list= [];
        $admin_list = UserAdmin::select('id','first_name','mobile_number','user_status','dob','doj','employee_no','profile_image','user_id','email_id');
        if(isset($request->search) && $request->search!='')
        {
        	$admin_list = $admin_list->where('first_name', 'like', '%' . $request->search . '%')->orWhere('mobile_number', 'like', '%' . $request->search . '%')->orWhere('dob', 'like', '%' . $request->search . '%')->orWhere('doj', 'like', '%' . $request->search . '%')->orWhere('employee_no', 'like', '%' . $request->search . '%');
        }
        $admin_list = $admin_list->get()->toArray();
        // $currentPage = LengthAwarePaginator::resolveCurrentPage(); // Get current page form url e.x. &page=1
        $currentPage = $request->page;
        $itemCollection = new Collection($admin_list); // Create a new Laravel collection from the array data
        $perPage = 10;
        // Slice the collection to get the items to display in current page
        $sortedCollection = $itemCollection->sortBy('id');
        $currentPageItems = $sortedCollection->values()->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems= new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);

        $paginatedItems->setPath($request->url()); // set url path for generted links
        $paginatedItems->appends($request->page);

        $tempdata = $paginatedItems->toArray();
        $member_admin_list['total'] = $tempdata['total'];
        $member_admin_list['per_page'] = $tempdata['per_page'];
        $member_admin_list['current_page'] = $tempdata['current_page'];
        $member_admin_list['last_page'] = $tempdata['last_page'];
        $member_admin_list['next_page_url'] = $tempdata['next_page_url'];
        $member_admin_list['prev_page_url'] = $tempdata['prev_page_url'];
        $member_admin_list['from'] = $tempdata['from'];
        $member_admin_list['to'] = $tempdata['to'];
        $list = ($currentPage <= 0)?$admin_list:$tempdata['data'];
        	
        foreach ($list as $key => $value) {
        	$check_access = SchoolUsers::where('user_id',$value['user_id'])->where('user_role',Config::get('app.Admin_role'))->where('user_status',2)->pluck('id')->first(); //2- full deactivate

        	if($check_access == '')
        		$check_access = UserGroupsMapping::where('user_table_id',$value['id'])->where('user_role',Config::get('app.Admin_role'))->where('user_status',1)->pluck('id')->first();

        	$member_admin_list['data'][]=([
	        	'id' => $value['id'],
	        	'first_name' => $value['first_name'],
	        	'mobile_number' => $value['mobile_number'],
	        	'user_id' => $value['user_id'],
	        	'email_id' => $value['email_id'],
	    	 	'dob' => $value['dob'],
	            'doj' => $value['doj'],
	            'employee_no' => $value['employee_no'],
	            'user_status' => ($check_access == '')?3:$value['user_status'], // 1- active,2-full deactive,3-partical deactive
	            'designation' => 'Admin',
	            'profile_image' => (isset($value['profile_image']))?$value['profile_image']:'',
	        ]);
        }
        // if($currentPage <= 0)
	    // 	$member_admin_list = $member_admin_list['data'];

        return response()->json($member_admin_list);
	}

	// All management list
	public function all_management_list(Request $request)
	{
		// Save last login in DB
        $user = auth()->user();
        $member_management_list = [];
        $management_list = UserManagements::select('id','first_name','mobile_number','user_category','user_status','dob','doj','employee_no','profile_image','user_id','email_id');
        if(isset($request->search) && $request->search!='')
        {
        	$category = (strpos('main head',strtolower($request->search)))?6:((strpos('chairman',strtolower($request->search)))?7:((strpos('principal',strtolower($request->search)))?8:((strpos('headmaster',strtolower($request->search)))?8:'')));
        	$management_list = $management_list->where('first_name', 'like', '%' . $request->search . '%')->orWhere('mobile_number', 'like', '%' . $request->search . '%')->orWhere('dob', 'like', '%' . $request->search . '%')->orWhere('doj', 'like', '%' . $request->search . '%')->orWhere('employee_no', 'like', '%' . $request->search . '%')->orWhere('department', 'like', '%' . $request->search . '%');
        	if($category!='')
        		$management_list = $management_list->orWhere('user_category', 'like', '%' . $category . '%');
        }
        $management_list = $management_list->get()->toArray();
        // $currentPage = LengthAwarePaginator::resolveCurrentPage(); // Get current page form url e.x. &page=1
        $currentPage = $request->page;
        $itemCollection = new Collection($management_list); // Create a new Laravel collection from the array data
        $perPage = 10;
        // Slice the collection to get the items to display in current page
        $sortedCollection = $itemCollection->sortBy('id');
        $currentPageItems = $sortedCollection->values()->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems= new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);

        $paginatedItems->setPath($request->url()); // set url path for generted links
        $paginatedItems->appends($request->page);

        $tempdata = $paginatedItems->toArray();
        $member_management_list['total'] = $tempdata['total'];
        $member_management_list['per_page'] = $tempdata['per_page'];
        $member_management_list['current_page'] = $tempdata['current_page'];
        $member_management_list['last_page'] = $tempdata['last_page'];
        $member_management_list['next_page_url'] = $tempdata['next_page_url'];
        $member_management_list['prev_page_url'] = $tempdata['prev_page_url'];
        $member_management_list['from'] = $tempdata['from'];
        $member_management_list['to'] = $tempdata['to'];
        $list = ($currentPage <= 0)?$management_list:$tempdata['data'];
        	
        foreach ($list as $key => $value) {
        	$check_access = SchoolUsers::where('user_id',$value['user_id'])->where('user_role',Config::get('app.Management_role'))->where('user_status',2)->pluck('id')->first(); //2- full deactivate

        	if($check_access == '')
        		$check_access = UserGroupsMapping::where('user_table_id',$value['id'])->where('user_role',Config::get('app.Management_role'))->where('user_status',1)->pluck('id')->first();

        	$designation = ($value['user_category']!='')? UserCategories::where('id',$value['user_category'])->pluck('category_name')->first():'';

        	$member_management_list['data'][]=([
        		'id' => $value['id'],
	        	'first_name' => $value['first_name'],
	        	'mobile_number' => $value['mobile_number'],
	        	'user_id' => $value['user_id'],
	        	'user_category' => $value['user_category'],
	        	'email_id' => $value['email_id'],
	        	'dob' => $value['dob'],
	            'doj' => $value['doj'],
	            'employee_no' => $value['employee_no'],
	            'user_status' => ($check_access == '')?3:$value['user_status'], // 1- active,2-full deactive,,3-partical deactive
	            'designation' => $designation,
	            'profile_image' => (isset($value['profile_image']))?$value['profile_image']:'',
	        ]);
        }
        // if($currentPage <= 0)
	    // 	$member_management_list = $member_management_list['data'];

        return response()->json($member_management_list);
	}

	// Check admission number already exists
	public function check_admission_unique(Request $request)
	{
		// Save last login in DB
        $user = auth()->user();

        $admission_no = $request->admission_no;
        if($admission_no !='')
        {
        	$check_exists = UserStudents::where('admission_number',$admission_no);
        	if($request->id!='')
        		$check_exists = $check_exists->where('id','!=',$request->id);
        	$check_exists = $check_exists->get()->toArray();

        	if(!empty($check_exists))
        		return response()->json(['status'=>false,'message'=>'Given admission number already exists']);
        	else
        		return response()->json(['status'=>true]);
        }
        else
        	return response()->json(['status'=>false,'message'=>'Admission number is required!...']);
	}

	public function CheckuserStatus(Request $request)
	{
		$user = auth()->user();
		if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $userid = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Management_role'))
        	$userid = UserManagements::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Staff_role'))
        	$userid = UserStaffs::select('id','user_category')->where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Parent_role'))
        	$userid = UserParents::where(['user_id'=>$user->user_id])->pluck('id')->first();

        // check deactivation for user
        $check_access = UserGroupsMapping::where('user_table_id',$userid)->where('group_id',2)->where('user_role',$user->user_role)->where('user_status',1)->pluck('id')->first();

        if($check_access == '')
            return response()->json(['status'=>false,'message'=>'Your account is deactivated. Please contact school management for futher details']);
        else
        	return response()->json(['status'=>true,'message'=>'']);
	}

	public function user_role_change(Request $request)
	{
		// Check authentication
		$user = auth()->user();
		$main_user_details = app('App\Http\Controllers\APILoginController')->get_user_table_id($user);

		$userall_id = UserAll::where(['user_table_id'=>$main_user_details->id,'user_role'=>$user->user_role])->pluck('id')->first();

		$profile_details = SchoolProfile::where(['id'=>$user->school_profile_id])->first();//Fetch school profile details

		$remove_groups = $add_groups = $change_status =[];
		$changing_role = $request->changing_role; //get input

		$original_role = $request->original_role;

		$original_user_id = $request->user_id;

		$user_data = (object) ([
			'user_id'=>$original_user_id,
			'user_role'=>$original_role
		]);

		$original_details = app('App\Http\Controllers\APILoginController')->get_user_table_id($user_data);

		$original_userall_id = UserAll::where(['user_table_id'=>$original_details->id,'user_role'=>$original_role])->pluck('id')->first();

		if(($changing_role == Config::get('app.Admin_role') || $changing_role == Config::get('app.Management_role')) && $original_role != $changing_role) //changing role to admin or managment
		{
		    if($changing_role == Config::get('app.Admin_role'))
		    	$change_table_details = new UserAdmin;
		    else if($changing_role == Config::get('app.Management_role'))
				$change_table_details = new UserManagements;
			else
				$change_table_details = new UserStaffs;

			if($original_role == Config::get('app.Management_role') )
			{

				$check_exists = UserAdmin::where('mobile_number',$original_details->mobile_number)->pluck('id')->first();
				if($check_exists=='')
				{
					$change_status = UserGroups::where('group_status',Config::get('app.Group_Active'))->where('id','!=',1)->pluck('id')->toArray();
					$changing_group_access = Config::get('app.Group_Active'); //changing access to group admin
					UserManagements::where('id',$original_details->id)->delete();
				}
				else
					return (['status'=>'false','message'=>'Mobile number already exists as admin']);
			}
			else if($original_role == Config::get('app.Admin_role'))
			{
				$check_exists = UserManagements::where('mobile_number',$original_details->mobile_number)->pluck('id')->first();
				if($check_exists=='')
				{
					if(isset($request->user_category) && $changing_role != Config::get('app.Admin_role') && $request->user_category>0)
						$change_table_details->user_category = $request->user_category;
					$change_status = UserGroups::where('group_status',Config::get('app.Group_Active'))->pluck('id')->toArray();
					$changing_group_access = Config::get('app.Group_Active');
					$group_access = 1;

					UserAdmin::where('id',$original_details->id)->delete();
				}
				else
					return (['status'=>'false','message'=>'Mobile number already exists as management']);
			}
			else if($original_role == Config::get('app.Staff_role'))
			{
				if(isset($request->user_category) && $changing_role != Config::get('app.Admin_role') && $request->user_category>0)
					$change_table_details->user_category = $request->user_category;
				$change_status = UserGroups::where('group_status',Config::get('app.Group_Active'))->where('id','!=',1)->pluck('id')->toArray();
				$changing_group_access = Config::get('app.Group_Active'); //changing access to group admin

				AcademicClassConfiguration::where('class_teacher',$original_details->id)->update(['class_teacher'=>null]);
				AcademicSubjectsMapping::where('staff',$original_details->id)->update(['staff'=>null]);

				UserStaffs::where('id',$original_details->id)->delete();
			}
		}
		else if($changing_role == Config::get('app.Staff_role')) //changing role to staff
		{
			$user_category = $request->user_category;
			if($user_category != '')
			{	
				$change_table_details = new UserStaffs;
				$removing_group = $classgroups =[];
				$removing_group =  ($user_category == Config::get('app.Teaching_staff'))? 5:4; //remove teaching or non-teaching staff based on category selection.
				AcademicSubjectsMapping::where('staff',$original_details->id)->update(['staff'=>null,'updated_by'=>$userall_id]);
				AcademicClassConfiguration::where('class_teacher',$original_details->id)->update(['class_teacher'=>null,'updated_by'=>$userall_id]);
				$deletinggroup_ids = UserGroups::where('group_type',2)->pluck('id')->toArray();
				$classgroups = UserGroupsMapping::whereIn('group_id',$deletinggroup_ids)->pluck('id')->toArray();

				$admin_management_group_id = UserGroups::where('group_name', 'like', '%Admin-Management%')->pluck('id')->first();
				$change_status = ([2,3]);
				$user_category_group[] = ($user_category == Config::get('app.Teaching_staff'))?4:5;
				$change_status = array_merge($change_status,$user_category_group);
				$remove_groups = ([1,$removing_group,$admin_management_group_id]);
				$remove_groups = array_merge($remove_groups,$deletinggroup_ids);
				$changing_group_access = Config::get('app.Group_Deactive'); //changing access to group admin

				if($original_role == Config::get('app.Admin_role'))
					UserAdmin::where('id',$original_details->id)->delete();
				else if($original_role == Config::get('app.Management_role'))
					UserManagements::where('id',$original_details->id)->delete();
			}
			else
				return response()->json(['status'=>false,'message'=>'User Category Required']);
		}

		$change_table_details->first_name= $original_details->first_name;
        $change_table_details->mobile_number=$original_details->mobile_number;
        $change_table_details->email_id = $original_details->emai_id;
        $change_table_details->profile_image = $original_details->profile_image;
        $change_table_details->user_id = $original_details->user_id;
        $change_table_details->dob = $original_details->dob;
        $change_table_details->doj = $original_details->doj;
        $change_table_details->employee_no = $original_details->employee_no;
        if(isset($request->user_category) && $changing_role != Config::get('app.Admin_role') && $request->user_category>0)
        	$change_table_details->user_category = $request->user_category;
	    $change_table_details->created_by=$userall_id;
    	$change_table_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
        $change_table_details->save();

        $id =$change_table_details->id;

        CommunicationRecipients::where('user_table_id',$original_details->id)->where('user_role',$original_role)->update(['user_table_id'=>$id,'user_role'=>$changing_role]); 

        // $user_id_char = ($changing_role == Config::get('app.Admin_role'))?'A':($changing_role == Config::get('app.Management_role')?'M':'T');

        // // generate and update staff id in db 
        // $updated_user_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).$user_id_char.sprintf("%04s", $id);
        // $change_table_details->user_id = $updated_user_id;
        // $change_table_details->save();

        $user_all = UserAll::where('id',$original_userall_id)->first();

        if(!empty($user_all))
        {
	        $user_all->user_role=$changing_role;
	        $user_all->user_table_id=$id;
	        $user_all->save();
        }


        $schoolusers = SchoolUsers::where('user_id',$original_details->user_id)->first();
        // $schoolusers->user_id=$updated_user_id;
        $schoolusers->user_role=$changing_role;
        $schoolusers->role_change = 1;
        $schoolusers->save();

		if(!empty($remove_groups))//remove groups
			$this->remove_groups($remove_groups,$original_details->id,$original_role);
		if(!empty($add_groups)) //add groups
			$this->add_groups($add_groups,$id,$changing_role,$group_access);

		if(!empty($change_status)) //add groups
			$this->change_status($change_status,$id,$changing_role,$changing_group_access,$original_details->id,$original_role);

		// if($changing_role == Config::get('app.Staff_role')
		// {
		// 	// given group admin access for class teacher group
		// 	$class_config = AcademicClassConfiguration::where('')
		// }

		return (['status'=>true,'message'=>'User role Changed']);
		exit;
	}

	// remove groups
	public function remove_groups($group_ids,$user_table_id,$user_role)
	{
		if(!empty($group_ids))
		{
			foreach ($group_ids as $key => $value) {
				// remove group access
				UserGroupsMapping::where('group_id',$value)->where('user_table_id',$user_table_id)->where('user_role',
						$user_role)->delete();
			}
		}
	}

	// add groups
	public function add_groups($group_ids,$user_table_id,$user_role,$group_access)
	{
		if(!empty($group_ids))
		{
			foreach ($group_ids as $key => $value) {
				// add group access
				$check_exists = UserGroupsMapping::where(['user_table_id'=>$user_table_id,'user_role'=>$user_role])->where('group_id',$value)->first();
				if(empty($check_exists))
				{
					UserGroupsMapping::insert(['group_id'=>$value,'user_table_id'=>$user_table_id,'group_access'=>$group_access,'user_role'=>$user_role]);
				}
			}
		}
	}
	// change groups access
	public function change_status($group_ids,$user_table_id,$user_role,$group_access,$original_id,$original_role)
	{
		if(!empty($group_ids))
		{
			foreach ($group_ids as $key => $value) {
				// add group access
				$check_exists = UserGroupsMapping::where(['user_table_id'=>$original_id,'user_role'=>$original_role])->where('group_id',$value)->first();
				$check_original_user_exists = UserGroupsMapping::insert(['user_table_id'=>$user_table_id,'user_role'=>$user_role,'group_access'=>1,'group_id'=>$value]);
				if(!empty($check_exists) && empty($check_original_user_exists))
				{
					if($user_role == Config::get('app.Admin_role') || $user_role == Config::get('app.Management_role') || $user_role == Config::get('app.Staff_role'))
					{
						if($value !=1 || ($value == 1 && $user_role == Config::get('app.Management_role')))
							$check_exists = $check_exists->update(['group_access'=>$group_access,'user_table_id'=>$user_table_id,'user_role'=>$user_role]);
					}
				}
				else if(empty($check_original_user_exists))
				{
					if($user_role == Config::get('app.Admin_role') || $user_role == Config::get('app.Management_role'))
					{
						if(($value == 1 && $user_role == Config::get('app.Management_role')) || $value!= 1)
						{
							UserGroupsMapping::insert(['user_table_id'=>$user_table_id,'user_role'=>$user_role,'group_access'=>1,'group_id'=>$value]);
						}
					}
				}
			}
		}
		UserGroupsMapping::where(['user_table_id'=>$original_id,'user_role'=>$original_role])->whereIn('group_id',$group_ids)->delete();
	}

	public function check_staff_classes(Request $request)
	{
		// Check authentication
		$user = auth()->user();

		$classteacher = AcademicClassConfiguration::where('class_teacher',$request->staff_id)->pluck('id')->first();

		$staffs = AcademicSubjectsMapping::where('staff',$request->staff_id)->pluck('id')->first();

		if($class_teacher == '' && $staffs == '')
			return (['status'=>true,'message'=>'']);
		else
			return (['status'=>false,'message'=>'Teacher was configured in some of the classes']);
	}

	public function check_user_role_changed(Request $request)
	{
		// Check authentication
		$user = auth()->user();
		$userdetails = SchoolUsers::where('user_id',$user->user_id)->first();
		$role_change = $userdetails->role_change;
		$token= Auth::login($userdetails);
		if($userdetails->role_change == 1)
		{
			$userdetails->role_change = 0;
			$userdetails->save();
		}

		$school_name = SchoolProfile::where('id',$userdetails->school_profile_id)->pluck('school_name')->first();//get all schools list

		return (['role_change'=>$role_change,'user_id'=>$userdetails->user_id,'user_role'=>$userdetails->user_role,'token'=>$token,'school_name'=>$school_name]);
	}

	// update parent details
	public function update_parent_details(Request $request)
	{
		// Check authentication
		$user = auth()->user();
		$student_id = $request->student_id;
		$image ='';
		$user_table_id = app('App\Http\Controllers\APILoginController')->get_user_table_id($user);
		$userall_id = UserAll::where(['user_table_id'=>$user_table_id->id,'user_role'=>$user->user_role])->pluck('id')->first(); //fetch id from user all table to store setting triggered user
		// insert parents details
    	$details = UserParents::where('id',$request->id)->get()->first(); //fetch parent details

    	$school_profile = SchoolProfile::where(['id'=>$user['school_profile_id']])->get()->first();//get school code from school profile
        if(!empty($details) || $request->mobile_number!='')
        {
        	// upload profile image
        	$target_file = '/parent/';
	    	if($request->photo!='')
	        {
	        	$image = app('App\Http\Controllers\WelcomeController')->profile_file_upload($school_profile['school_code'],$request->photo,1,$target_file,$request->ext);
	        }

        	// if(count($_FILES)>0)
	        // {
	        //     if($request->hasfile('photo')) {
	        //         $image = app('App\Http\Controllers\WelcomeController')->profile_file_upload($school_profile['school_code'],$request->file('photo'),1,$target_file);
	        //     }           
	        // }

	        // check mobile no already exist in table.
	        $check_exists = UserParents::where('mobile_number',$request->mobile_number);
	        
	        if(isset($request->id)!='')
	            $check_exists = $check_exists->where('id','!=',$request->id);

	        $check_exists = $check_exists->first();

	        if(!empty($check_exists) && $request->mobile_number != $details->mobile_number) //if exists
	        {
	        	$old_parent_id = $request->id;
	        	$new_parent_id = $check_exists->id;
	        	
	        	$student_list = UserStudentsMapping::where('parent',$old_parent_id)->pluck('student')->toArray(); // fetch all the student ids related to old parent.

	        	foreach($student_list as $student_key => $student_value)
	        	{	     	        		
	        		// update parent ids to new parent id in table
		        	UserStudentsMapping::where('parent',$old_parent_id)->update(['parent'=>$new_parent_id]);

		        	$class_config = UserStudents::where('id',$student_value)->pluck('class_config')->first(); //fetch class config for the student

		        	$group_id = UserGroups::where('class_config',$class_config)->pluck('id')->first(); //get group id to reassign parent id

		        	$check_group_exists = UserGroupsMapping::where('user_table_id',$new_parent_id)->where('group_id',$group_id)->where('user_role',Config::get('app.Parent_role'))->first();

		        	if(!empty($check_group_exists)) //delete old parent group id, if new already have the same group
		        	{
		        		UserGroupsMapping::where('user_table_id',$old_parent_id)->where('group_id',$group_id)->where('user_role',Config::get('app.Parent_role'))->delete();
		        	}
		        	else
		        	{
		        		UserGroupsMapping::where('user_table_id',$old_parent_id)->where('group_id',$group_id)->where('user_role',Config::get('app.Parent_role'))->update(['user_table_id'=>$new_parent_id]);
		        	}

		        	SchoolUsers::where('user_id',$details->user_id)->where('school_profile_id',$user->school_profile_id)->delete();
		        	UserParents::where('id',$old_parent_id)->delete();		           		      
	        	}
	        	$details = UserParents::where('id',$new_parent_id)->first();
	        }

	        $userparent_id = $details->user_id;
	        $parent_id = $details->id; 	

	        //save parent details
	        if($request->name!='')
	            $details->first_name=  $request->name;
	        if($request->mobile_number!='')
	            $details->mobile_number=$request->mobile_number;
	        if($image!='')
	            $details->profile_image = ($image!='')?$image:'';
	        if($request->email_address!='')
	            $details->email_id=$request->email_address;
	        if($request->user_category!='')
	            $details->user_category = $request->user_category;
	        $details->updated_by=$userall_id;
	        $details->updated_time=Carbon::now()->timezone('Asia/Kolkata');
	        $details->save();
	        $parent_id = $details->id;
        	
	        $schoolusers = SchoolUsers::where(['user_id'=>$userparent_id,'school_profile_id'=>$user->school_profile_id])->first();
	        if(!empty($schoolusers))
	        {
		        if($request->mobile_number!='')
	            	$schoolusers->user_mobile_number=$request->mobile_number;
	            if($request->email_address!='')
	            	$schoolusers->user_email_id=$request->email_address;
	            $schoolusers->user_role=Config::get('app.Parent_role');
	            $schoolusers->save();
	        }
        	return response()->json(['status'=>true,'message'=>'Parent details updated Successfully!...']);
        }
        return response()->json(['status'=>false,'message'=>'Failed to updated parent details!...']);
	}

	// check mobile number exists
	public function parentcheckMobileno(Request $request)
    {

    	$dulpicate_check_exists = UserParents::where('mobile_number',$request->mobile_number);
        if($request->user_category == Config::get('app.Father'))
            $dulpicate_check_exists = $dulpicate_check_exists->whereIn('user_category',([Config::get('app.Mother'),Config::get('app.Guardian')]));
        else if($request->user_category == Config::get('app.Mother'))
            $dulpicate_check_exists = $dulpicate_check_exists->whereIn('user_category',([Config::get('app.Father'),Config::get('app.Guardian')]));
        else if($request->user_category == Config::get('app.Guardian'))
            $dulpicate_check_exists = $dulpicate_check_exists->whereIn('user_category',([Config::get('app.Mother'),Config::get('app.Father')]));
        else
        	return response()->json(['status'=>false,'message'=>'Permission Denied!..']);

        if(isset($request->id)!='')
            $dulpicate_check_exists = $dulpicate_check_exists->where('id','!=',$request->id);

        $dulpicate_check_exists = $dulpicate_check_exists->first();
        if(!empty($dulpicate_check_exists))
        	return response()->json(['status'=>false,'tag'=>'duplicate']);
        else
        {
        	$check_exists = UserParents::where('mobile_number',$request->mobile_number)->where('user_category',$request->user_category);
        
	        if(isset($request->id) && $request->id!='')
	            $check_exists = $check_exists->where('id','!=',$request->id);

	        $check_exists = $check_exists->first();

	        if(!empty($check_exists))
            	return response()->json(['status'=>false,'tag'=>'map']);
	        else
            	return response()->json(['status'=>true,'tag'=>'']);
        }
    }

    // check employee number exists
	public function parentcheckEmployeeno(Request $request)
    {
    	$user_role = $request->user_role;
    	$employee_no = $request->employee_no;
    	$id = $request->id;
		if($user_role == Config::get('app.Admin_role'))
        	$check_exists = UserAdmin::where('employee_no',$employee_no);
        else if($user_role == Config::get('app.Staff_role'))
        	$check_exists = UserStaffs::where('employee_no',$employee_no);
        else
        	$check_exists = UserManagements::where('employee_no',$employee_no);
        
        if(isset($id) && $id!='')
            $check_exists = $check_exists->where('id','!=',$id);
        $check_exists = $check_exists->first();

        if(!empty($check_exists))
        	return response()->json(['status'=>false]);
        else
        	return response()->json(['status'=>true]);
    }
}