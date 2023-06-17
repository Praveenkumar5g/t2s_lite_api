<?php
/**
 * Created by PhpStorm.
 * User: prakashjagannathan
 * Date: 11/04/19
 * Time: 12:38 PM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PfMixedConfig extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_mixed_config";
    public $timestamps = false;
    protected $guarded = [];

}
