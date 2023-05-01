<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 04-03-2023
 * Time: 04:00
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeworkParentStatus extends Model
{
    protected $connection = "school_db";
    protected $table = "homework_parent_status";
    protected $guarded = [];
    public $timestamps = false;

        public function studentDetails()
    {
    	$user_details =UserStudents::where(['id'=>$this->student])->first()->toArray();
        if(count($user_details) > 0) {
                return $user_details;
        } else {
                return "";
        }
    }
    public function parentDetails()
    {
    	$user_details =UserParents::where(['id'=>$this->parent])->first()->toArray();
        if(count($user_details) > 0) {
                return $user_details;
        } else {
                return "";
        }
    }
}