<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PfOnlineReceipts extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_online_receipts";
    protected $guarded = array();
}