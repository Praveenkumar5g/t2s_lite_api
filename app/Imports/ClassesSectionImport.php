<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 02-01-2023
 * Time: 07:40
 * Import class data in DB 
 */
namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\AcademicSections;
use App\Models\AcademicClasses;
use App\Models\Configurations;
use App\Models\UserAdmin;
use App\Models\UserAll;
use Carbon\Carbon;

class ClassesSectionImport implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
    	// Get authorizated user details
        $user_data = auth()->user();

        if($user_data->user_role == 1)
            $user_admin = UserAdmin::where(['user_id'=>$user_data->user_id])->pluck('id')->first();

        $userall_id = UserAll::where(['user_table_id'=>$user_admin,'user_role'=>$user_data->user_role])->pluck('id')->first(); //fetch id from user all table to store setting triggered user
        $inserted_records=0;
        //Process each and every row ,insert all data in db
        foreach ($collection as $row) {
            if($row['class_name']!='' && $row['section_1']!='')
            {

                $class_id = AcademicClasses::where(['class_name'=>$row['class_name']])->pluck('id')->first(); 
                if($class_id=='')
                {
                    $academicclasses = new AcademicClasses;
                    $academicclasses->class_name = $row['class_name'];
                    $academicclasses->created_by = $userall_id;
                    $academicclasses->created_time = Carbon::now()->timezone('Asia/Kolkata');
                    $academicclasses->save();

                    $class_id = $academicclasses->id;
                }
                for ($sections_count=1; $sections_count <= 10; $sections_count++) { 
                    if($row['section_'.$sections_count]!='')
                    {
                        $sections_id = AcademicSections::where(['section_name'=>$row['section_'.$sections_count]])->pluck('id')->first(); 
                        if($sections_id=='')
                        {
                            $academicsections = new AcademicSections;
                            $academicsections->section_name = $row['section_'.$sections_count];
                            $academicsections->created_by = $userall_id;
                            $academicsections->created_time = Carbon::now()->timezone('Asia/Kolkata');
                            $academicsections->save();

                            $sections_id = $academicsections->id;
                            $inserted_records++;
                        }
                    }

                }
            }
        }

        if($inserted_records>0)
            Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['classes_sections'=>1]);
    }
}
