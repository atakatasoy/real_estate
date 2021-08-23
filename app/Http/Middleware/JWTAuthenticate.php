<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use App\Core\Auth\TokenHandler;

use App\Core\Auth\Exceptions\UnauthenticatedUserException;

class JWTAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try{
            $user = app()->make(TokenHandler::class)->attemptToLogin();
        }catch(UnauthenticatedUserException $e){
            //BTW this should be a view not a POST endpoint
            return redirect('/');
        }
        
        $request->attributes->add(['user' => $user]);

        return $next($request);
    }
}
