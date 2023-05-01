<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 26-12-2022
 * Time: 10:45
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationAttachments extends Model
{
    protected $connection = "school_db";
    protected $table = "communication_attachments";
    protected $guarded = [];
    public $timestamps = false;
}