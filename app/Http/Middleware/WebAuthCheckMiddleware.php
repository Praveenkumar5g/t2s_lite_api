<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 27-12-2022
 * Time: 11:15
 * Validate authenticate for each and every api call
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\SchoolProfile;
use App\Models\SchoolDatabase;
use App\Http\Controllers\APIRegistrationController;
use Session;

class WebAuthCheckMiddleware
{
    public function handle($request, Closure $next)
    {   
        if(Session::has('token') && Session::get('token')!='')
        {
            try {
                $user = Session::get('user_data');
                $this->getDb($user->school_profile_id);
                // valid credentails and allow proceed 
                return $next($request);
            } catch (JWTException $e) { // invalid token and thrown an exception
                return redirect()->intended('/');
            }
        }
        return redirect()->intended('/'); 
    }   

    public function getDb($school_id)
    {
        $school_profile = SchoolProfile::where('id',$school_id)->first(); //get school profile details from corresponding school
        $academic_year = $school_profile->active_academic_year;
        $config_school = SchoolDatabase::where('school_id', $school_id)->where('academic_year',$academic_year)->get()->first();
        if(empty($config_school))
            APIRegistrationController::create_profile();
        else
        {
            Config::set('database.connections.school_db.host',$config_school->school_db_host);
            Config::set('database.connections.school_db.username',$config_school->school_db_user);
            Config::set('database.connections.school_db.password',$config_school->school_db_pass);
            Config::set('database.connections.school_db.database',$config_school->school_db_name);
            DB::reconnect('school_db');
        }
    }
}
