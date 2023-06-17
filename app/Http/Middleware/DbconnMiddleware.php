<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 27-12-2022
 * Time: 10:20
 * Validate authenticate for each and every api call
 */

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\SchoolProfile;
use App\Models\SchoolDatabase;
use App\Http\Controllers\APIRegistrationController;

class DbconnMiddleware
{
    public function handle($request, Closure $next)
    {   
        // check whether the user is authorized or not
        if ($user = auth()->user()) {
            if($user['user_status'] == 1)
            {
                try {
                    $this->getDb($user['school_profile_id']);
                    // valid credentails and allow proceed 
                    return $next($request);
                } catch (JWTException $e) { // invalid token and thrown an exception
                    return response()->json(['error' => 'Token Error'], 500);
                }
            }
            else
                return response()->json(['status'=>false,'error' => 'Your is account deactived!..']);
        }else // invalid token and thrown an exception
        {
            return response()->json(['error' => 'Invalid token'], 401);
        }
    }

    /**
     * @param $school_id
     * This function get the school id from the user login password and connect respective database dynamically
     */
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
