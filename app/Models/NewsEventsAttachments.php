<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 17-04-2023
 * Time: 12:20
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsEventsAttachments extends Model
{
    protected $connection = "school_db";
    protected $table = "news_events_attachments";
    protected $guarded = [];
    public $timestamps = false;
}