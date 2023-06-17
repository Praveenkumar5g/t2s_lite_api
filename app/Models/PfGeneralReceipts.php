<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PfGeneralReceipts extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_general_receipts";
    protected $guarded = array();
}