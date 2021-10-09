<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class IsAdmin
{
    public function handle($request, Closure $next, ...$guards)
    {
        if(Auth::guard('api')->check()){
            if (!Auth::guard('api')->user()->is_admin){
                http_response_code(403);
                exit("Unauthorized action.");
            }
            return $next($request);
        }
        return app(Authenticate::class)->handle($request, function ($request) use ($next) {
            if (!auth()->user()->is_admin){
                abort(403, 'Unauthorized action.');
            }

            //Then process the next request if every tests passed.
            return $next($request);
        });
    }
}
