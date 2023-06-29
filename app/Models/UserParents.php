<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 26-12-2022
 * Time: 10:45
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserParents extends Model
{
    protected $connection = "school_db";
    protected $table = "user_parents";
    protected $guarded = [];
    public $timestamps = false;

    public function classsectionName()
    {
        $classsection_id = AcademicClassConfiguration::select('class_id','section_id')->where('id',$this->class_config)->first();
        $classname =AcademicClasses::where(['id'=>$classsection_id->class_id])->pluck('class_name')->first();
        $sectionname =AcademicSections::where(['id'=>$classsection_id->section_id])->pluck('section_name')->first();
        if($classname!= '' && $sectionname!='') 
            return $classname.' - '.$sectionname;
        else if($classname!= '' && $sectionname=='') 
            return $classname;
        else if($classname== '' && $sectionname!='') 
            return $sectionname;
        else
            return "";
    }
}