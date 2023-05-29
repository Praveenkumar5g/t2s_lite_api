<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 29-12-2022
 * Time: 11:00
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalSettings extends Model
{
    protected $connection = "school_db";
    protected $table = "approval_settings";
    protected $guarded = [];
    public $timestamps = false;
}