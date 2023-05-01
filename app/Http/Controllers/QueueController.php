<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\CheckQueue;

class QueueController extends Controller
{
    public function sendMail()
    {
    	dispatch(new CheckQueue())->onQueue('copydb');
    	dd('success');
    }
}
