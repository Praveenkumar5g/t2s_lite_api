<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 03-01-2023
 * Time: 10:00
 * Import subjects data in DB 
 */
namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\AcademicClassConfiguration;
use App\Models\SchoolAcademicYears;
use App\Models\AcademicSubjectsMapping;
use App\Models\AcademicSubjects;
use App\Models\AcademicSections;
use App\Models\AcademicClasses;
use App\Models\UserCategories;
use App\Models\Configurations;
use App\Models\SchoolProfile;
use App\Models\SchoolUsers;
use App\Models\UserStaffs;
use App\Models\UserAdmin;
use App\Models\UserAll;
use Carbon\Carbon;
use DB;

class StaffsImport implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        // Get authorizated user details
        $user_data = auth()->user();

        if($user_data->user_role == 1)//check role and get current user id
            $user_admin = UserAdmin::where(['user_id'=>$user_data->user_id])->pluck('id')->first();

        $userall_id = UserAll::where(['user_table_id'=>$user_admin,'user_role'=>$user_data->user_role])->pluck('id')->first(); //fetch id from user all table to store setting triggered user

        // Fetch staff category id from role
        $user_categories = array_column(UserCategories::where('user_role',2)->get()->toArray(),'id','category_name');
        // fetch academic year
        $academicyear = SchoolAcademicYears::where(['school_profile_id'=>$user_data->school_profile_id])->pluck('academic_year')->first();

        // Fetch subjects id from table
        $subjects = array_column(AcademicSubjects::get()->toArray(),'id','subject_name');
        $classes = array_column(AcademicClasses::get()->toArray(),'id','class_name'); //To check given class name is already exists in DB.

        $sections = array_column(AcademicSections::get()->toArray(),'id','section_name'); //To check given section name is already exists in DB.

        $profile_details = SchoolProfile::where(['id'=>$user_data->school_profile_id])->first();//Fetch school profile details 

        $staffs=$usermobile_numbers= [];
        $inserted_records=0;
        //Process each and every row ,insert all data in db
        foreach ($collection as $row) {
            if($row['staff_name']!='' && $row['mobile_number']!='' && $row['category']!='' )
            {   
                $check_exists = UserStaffs::where(['mobile_number'=>$row['mobile_number']])->pluck('id')->first(); //To check given subject name is already exists in DB.
                if($check_exists == '' && !in_array($row['mobile_number'],$usermobile_numbers) ) //if no then insert 
                {
                    $class_id = isset($classes[$row['class_nameclass_teacher_for']])?$classes[$row['class_nameclass_teacher_for']]:'';

                    $section_id = isset($sections[$row['section_nameclass_teacher_for']])?$sections[$row['section_nameclass_teacher_for']]:'';

                    array_push($usermobile_numbers, $row['mobile_number']);//check mobile number already exists in array
                    //arrange staff details in array 
                    $staffs_details = new UserStaffs;
                    $staffs_details->first_name= $row['staff_name'];
                    $staffs_details->mobile_number=$row['mobile_number'];
                    $staffs_details->email_id=$row['email_address'];
                    $staffs_details->user_category=isset($user_categories[$row['category']])?$user_categories[$row['category']]:'';
                    $staffs_details->specialized_in=isset($subjects[$row['specialized_in']])?$subjects[$row['specialized_in']]:'';
                    $staffs_details->created_by=$userall_id;
                    $staffs_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
                    $staffs_details->save();

                    $staff_id =$staffs_details->id; // staff id

                    if($class_id!='' && $section_id!='')
                    {
                        $class_config_id = AcademicClassConfiguration::where(['class_id'=>$class_id,'section_id'=>$section_id])->pluck('id')->first();

                        if($class_config_id!= '')
                            AcademicClassConfiguration::where(['id'=>$class_config_id])->update(['class_teacher'=>$staff_id,'updated_by'=>$userall_id,'updated_time'=>Carbon::now()->timezone('Asia/Kolkata')]);
                        else
                            AcademicClassConfiguration::insert(['class_id'=>$class_id,'section_id'=>$section_id,'class_teacher'=>$staff_id,'created_by'=>$userall_id,'created_time'=>Carbon::now()->timezone('Asia/Kolkata')]);
                    }
                    for ($teacher=1; $teacher <=8; $teacher++) { 
                        if(isset($subjects[$row['specialized_in']]) && $subjects[$row['specialized_in']]!='' && $row['class_name_'.$teacher.'subject_teacher_for']!='')
                        {
                            $class_id = isset($classes[$row['class_name_'.$teacher.'subject_teacher_for']])?$classes[$row['class_name_'.$teacher.'subject_teacher_for']]:'';

                            $section_id = isset($sections[$row['section_name_'.$teacher.'subject_teacher_for']])?$sections[$row['section_name_'.$teacher.'subject_teacher_for']]:'';

                            $class_config_id = AcademicClassConfiguration::where(['class_id'=>$class_id,'section_id'=>$section_id])->pluck('id')->first();
                            if($class_config_id=='')
                            {
                                $academicclassconfig = new AcademicClassConfiguration;
                                $academicclassconfig->academic_year=$academicyear;
                                $academicclassconfig->class_id=$class_id;
                                $academicclassconfig->section_id=$section_id;
                                $academicclassconfig->class_teacher=$staff_id;
                                $academicclassconfig->created_by=$userall_id;
                                $academicclassconfig->created_time=Carbon::now()->timezone('Asia/Kolkata');
                                $academicclassconfig->save();
                                $class_config_id = $academicclassconfig->id;
                            }
                            $mapping_id = AcademicSubjectsMapping::where(['class_config'=>$class_config_id,'subject'=>$subjects[$row['specialized_in']]])->pluck('id')->first();
                            if($mapping_id != '')
                                AcademicSubjectsMapping::where('id',$mapping_id)->update(['staff'=>$staff_id,'updated_by'=>$userall_id,'updated_time'=>Carbon::now()->timezone('Asia/Kolkata')]);
                            else
                                AcademicSubjectsMapping::insert(['class_config'=>$class_config_id,'subject'=>$subjects[$row['specialized_in']],'staff'=>$staff_id,'created_by'=>$userall_id,'created_time'=>Carbon::now()->timezone('Asia/Kolkata')]);
                        }
                    }
                    

                    // generate and update staff id in db 
                    $userstaff_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'T'.sprintf("%04s", $staff_id);
                    $staffs_details->user_id = $userstaff_id;
                    $staffs_details->save();


                    $user_all = new UserAll;
                    $user_all->user_table_id=$staff_id;
                    $user_all->user_role=2;
                    $user_all->save();

                    $schoolusers = new SchoolUsers;
                    $schoolusers->school_profile_id=$user_data->school_profile_id;
                    $schoolusers->user_id=$userstaff_id;
                    $schoolusers->user_mobile_number=$row['mobile_number'];
                    $schoolusers->user_password=bcrypt($row['mobile_number']);
                    $schoolusers->user_email_id=$row['email_address'];
                    $schoolusers->user_role=2;
                    $schoolusers->user_status=2;
                    $schoolusers->save();

                    
                    $inserted_records++;
                }
            }
        }

        if(!empty($usermobile_numbers)) //check empty array
            Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['staffs'=>1]);
    }
}
