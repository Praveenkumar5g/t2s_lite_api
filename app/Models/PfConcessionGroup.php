<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PfConcessionGroup extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_concession_group";
    protected $primaryKey = 'id';
    protected $guarded = [];

}
