<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PfAcademic extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_academic";
    protected $guarded = [];
}
