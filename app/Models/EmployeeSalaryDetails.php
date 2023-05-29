<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 25-05-2023
 * Time: 06:30
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryDetails extends Model
{
    protected $connection = "school_db";
    protected $table = "employee_salary_details";
    protected $guarded = [];
    public $timestamps = false;
}