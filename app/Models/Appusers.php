<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 21-03-2023
 * Time: 05:00
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appusers extends Model
{
    protected $connection = "school_db";
    protected $table = "app_users";
    protected $guarded = [];
    public $timestamps = false;
}