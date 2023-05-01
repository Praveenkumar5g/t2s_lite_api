<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 17-04-2023
 * Time: 12:10
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsEvents extends Model
{
    protected $connection = "school_db";
    protected $table = "news_events";
    protected $guarded = [];
    public $timestamps = false;
}