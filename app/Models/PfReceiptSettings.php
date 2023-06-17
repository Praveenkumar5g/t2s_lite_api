<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PfReceiptSettings extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_receipt_settings";
    protected $guarded = [];
}
