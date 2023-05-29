<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 20-04-2023
 * Time: 16:50
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsEventAcceptStatus extends Model
{
    protected $connection = "school_db";
    protected $table = "news_event_accept_status";
    protected $guarded = [];
    public $timestamps = false;
}