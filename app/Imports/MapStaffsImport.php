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
use App\Models\AcademicSections;
use App\Models\AcademicClasses;
use App\Models\Configurations;
use App\Models\UserStaffs;
use App\Models\UserAdmin;
use App\Models\UserAll;
use Carbon\Carbon;
use DB;

class MapStaffsImport implements ToCollection, WithHeadingRow
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
        $classes = $sections = [];

        $academicyear = SchoolAcademicYears::where(['school_profile_id'=>$user_data->school_profile_id])->pluck('academic_year')->first();
        
        $inserted_records=0;
        //Process each and every row ,insert all data in db
        foreach ($collection as $row) {
             if($row['class_name']!='' && $row['section_name']!='' && $row['class_teacher']!='')
            {                   
                $class_id = AcademicClasses::where(DB::raw('lower(class_name)'), strtolower($row['class_name']))->pluck('id')->first(); //To check given subject name is already exists in DB.
                if($class_id == '' && !in_array($row['class_name'],$classes)) 
                {
                    array_push($classes, $row['class_name']);//check mobile number already exists in array 

                    // Insert classes in table
                    $academicclasses = new AcademicClasses;
                    $academicclasses->class_name = $row['class_name'];
                    $academicclasses->created_by=$userall_id;
                    $academicclasses->created_time=Carbon::now()->timezone('Asia/Kolkata');
                    $academicclasses->save();
                    $class_id = $academicclasses->id;
                }

                $section_id = AcademicSections::where(DB::raw('lower(section_name)'), strtolower($row['section_name']))->pluck('id')->first(); //To check given subject name is already exists in DB.
                if($section_id == '' && !in_array($row['section_name'],$sections)) 
                {
                    array_push($sections, $row['section_name']);//check mobile number already exists in array 

                    // Insert Sections in table
                    $academicsections = new AcademicSections;
                    $academicsections->section_name = $row['section_name'];
                    $academicsections->created_by=$userall_id;
                    $academicsections->created_time=Carbon::now()->timezone('Asia/Kolkata');
                    $academicsections->save();
                    $section_id = $academicsections->id;
                }

                if($class_id != '' && $section_id!='')
                {
                    //check teacher exists in db
                    $classteacher_id = UserStaffs::where(['mobile_number'=>$row['class_teacher'],'user_category'=>3])->pluck('id')->first();
                    if($classteacher_id!='')
                    {
                        $class_config_id = AcademicClassConfiguration::where(['class_id'=>$class_id,'section_id'=>$section_id,'class_teacher'=>$classteacher_id])->pluck('id')->first(); //To check given subject name is already exists in DB.
                        if($class_config_id=='')
                        {
                            // Insert configuration in table
                            $class_config = new AcademicClassConfiguration;
                            $class_config->academic_year = $academicyear;
                            $class_config->class_id = $class_id;
                            $class_config->section_id = $section_id;
                            $class_config->class_teacher = $classteacher_id;
                            $class_config->created_by=$userall_id;
                            $class_config->created_time=Carbon::now()->timezone('Asia/Kolkata');
                            $class_config->save();
                            $inserted_records++;
                        }
                    }
                }

            }
        }
        if(!empty($classes) && !empty($sections))
            Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['map_staffs'=>1]);
    }
}
