<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 03-01-2023
 * Time: 02:05
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCategories extends Model
{
    protected $connection = "school_db";
    protected $table = "user_categories";
    protected $guarded = [];
    public $timestamps = false;
}