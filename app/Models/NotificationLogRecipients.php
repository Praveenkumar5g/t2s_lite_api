<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 28-12-2022
 * Time: 10:54
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLogRecipients extends Model
{
    protected $connection = "school_db";
    protected $table = "notification_log_recipients";
    protected $guarded = [];
    public $timestamps = false;
}