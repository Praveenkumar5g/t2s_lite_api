<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PfConfigWizard extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_config_wizard";
    protected $primaryKey = 'wizard_id';
    public $timestamps = false;
    protected $guarded = [];

}
