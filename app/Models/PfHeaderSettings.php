<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PfHeaderSettings extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_header_settings";
    protected $guarded = [];
}
