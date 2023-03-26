<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
//        if (Auth::guard($guard)->check()) {
//            return redirect('/home');
//        }
        if ($guard == "admin") {
            if (Auth::guard($guard)->check()) {
                return redirect('/admin');
            }
        } elseif($guard == null) {
            if (Auth::guard($guard)->check() &&
                ! $request->is('email/*', 'logout', 'password/*')) {
                return redirect('/');
            }
        }

        return $next($request);
    }
}
