<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 28-12-2022
 * Time: 10:54
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLogs extends Model
{
    protected $connection = "school_db";
    protected $table = "notification_logs";
    protected $guarded = [];
    public $timestamps = false;
}