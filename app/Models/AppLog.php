<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 02-06-2023
 * Time: 13:50
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppLog extends Model
{
    protected $connection = "school_db";
    protected $table = "app_log";
    protected $guarded = [];
    public $timestamps = false;
}