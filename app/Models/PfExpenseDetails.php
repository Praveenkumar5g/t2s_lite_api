<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PfExpenseDetails extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_expense_details";
    protected $primaryKey = 'id';
    protected $guarded = [];
}