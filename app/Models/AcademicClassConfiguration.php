<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 03-01-2023
 * Time: 04:00
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicClassConfiguration extends Model
{
    protected $connection = "school_db";
    protected $table = "academic_class_configuration";
    protected $guarded = [];
    public $timestamps = false;

    public function className()
    {
    	$classname =AcademicClasses::where(['id'=>$this->class_id])->pluck('class_name')->first();
        if($classname!= '') 
            return $classname;
        else
            return "";
    }

    public function sectionName()
    {
    	$sectionname =AcademicSections::where(['id'=>$this->section_id])->get()->first();
        if(!empty($sectionname)) 
            return $sectionname;
        else
            return "";
    }

    public function classsectionName()
    {
        $classsection_id = AcademicClassConfiguration::select('class_id','section_id')->where('id',$this->id)->first();
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