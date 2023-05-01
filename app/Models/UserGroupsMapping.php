<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 12-01-2023
 * Time: 11:15
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGroupsMapping extends Model
{
    protected $connection = "school_db";
    protected $table = "user_groups_mapping";
    protected $guarded = [];
    public $timestamps = false;
}