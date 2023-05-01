<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 03-01-2023
 * Time: 05:20
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSubjectsMapping extends Model
{
    protected $connection = "school_db";
    protected $table = "academic_subjects_mapping";
    protected $guarded = [];
    public $timestamps = false;

    public function subjectName()
    {
    	$subject_name =AcademicSubjects::where(['id'=>$this->subject])->pluck('subject_name')->first();
        if($subject_name!= '') 
            return $subject_name;
        else
            return "";
    }
}