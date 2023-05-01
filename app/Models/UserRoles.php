<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 03-01-2023
 * Time: 06:30
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRoles extends Model
{
    protected $connection = "school_db";
    protected $table = "user_roles";
    protected $guarded = [];
    public $timestamps = false;
}