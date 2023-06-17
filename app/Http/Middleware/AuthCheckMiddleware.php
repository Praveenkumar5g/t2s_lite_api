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
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AuthCheckMiddleware
{
    public function handle($request, Closure $next)
    {   
        // check whether the user is authorized or not
        if ($user = auth()->user()) {
            if($user['user_status'] == 1)
            {
                try {
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
}
