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
}