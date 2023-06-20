<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 24-05-2023
 * Time: 05:30
 * Validate inputs ,created DB for individual school and registed user details in config and school DB
 */
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\SchoolUsers;
use Validator;
use JWTFactory;
use JWTAuth;
use Session;

class WebLoginController extends Controller
{    
    // login function
    public function index()
    {
        if(Session::has('token') && Session::has('id') && Session::has('user_data')) //check users already logged in
            return redirect()->intended('employee/salarydetails'); //if yes redirect to main page
        return view('login'); //else redirect to login page
    }

    // login verification
    public function login(Request $request)
    {
        // Add rules to the login form
        $validator = Validator::make($request->all(), [
            'user_email_id' => 'required_without_all:user_mobile_number',
            'user_mobile_number' => 'required_without_all:user_email_id',
            'password'=> 'required',
            'user_role'=>'required', //1-admin,2-staff,3-parent,4-student,5-management
        ]);

        // Validate login form
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        if(isset($request->user_email_id))
            $credentials = $request->only('user_email_id', 'password','user_role');
        else
            $credentials = $request->only('user_mobile_number', 'password','user_role');
    
        $credentials['user_status'] = 1;
        try {
            // Generate token if credentails are valid else thrown error message.
            if ($token = Auth::attempt($credentials)) 
            {
                Session::put('token',$token);
                Session::put('id',Auth::id());
                Session::put('user_data',Auth::user());
                return redirect()->intended('usermanagement/students'); //redirect to salary upload page
            }
            else
                return back()->with('error','Invalid credentials!...');
        } catch (JWTException $e) 
        {
            return back()->with('error','Invalid credentials!...');
        }
    }

    public function apptoweblogin(Request $request)
    {
        // echo base64_encode(base64_encode('ohna22a0001'));exit; //YjJodVlUSXlZVEF3TURFPQ==

        $user_id = base64_decode(base64_decode($request->id));

        $user_data = SchoolUsers::where('user_id',$user_id)->get()->first();

        if(!empty($user_data))
        {
            if ($token = Auth::login($user_data)) 
            {
                Session::put('token',$token);
                Session::put('id',Auth::id());
                Session::put('user_data',Auth::user());
                if($request->menu === 'salary')
                    return redirect()->intended('employee/salarydetails'); //redirect to salary upload page
                else
                {
                    Session::flush();
                    return redirect()->intended('/')->with('error','Invalid Menu!...');
                }
            }
            else
                return redirect()->intended('/')->with('error','Invalid credentials!...');
        }
        return redirect()->intended('/')->with('error','Invalid ID!...');
    }

    // logout 
    public function logout()
    {
        Session::flush();
        return redirect()->intended('/'); 
    }
}