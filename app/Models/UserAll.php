<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 26-12-2022
 * Time: 10:45
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAll extends Model
{
    protected $connection = "school_db";
    protected $table = "user_all";
    protected $guarded = [];
    public $timestamps = false;

    public function userDetails()
    {
        $user_details =[];
        if($this->user_role == Config::get('app.Management_role'))
            $user_details =UserManagements::where(['id'=>$this->user_table_id])->first();
        else if($this->user_role == Config::get('app.Staff_role'))
            $user_details =UserStaffs::where(['id'=>$this->user_table_id])->first();
        else if($this->user_role == Config::get('app.Parent_role'))
            $user_details =UserParents::where(['id'=>$this->user_table_id])->first();
        else if($this->user_role == Config::get('app.Admin_role'))
            $user_details =UserAdmin::where(['id'=>$this->user_table_id])->first();
        if(!empty($user_details))
            return $user_details->toArray();
    }
}