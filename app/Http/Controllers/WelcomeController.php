<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 26-12-2022
 * Time: 05:15
 * Validate inputs ,created DB for individual school and registed user details in config and school DB
 */
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;

class WelcomeController extends Controller
{    
    public function welcome()
    {
        echo 'Welcome';exit();  
    }
}