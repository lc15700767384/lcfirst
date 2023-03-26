<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Closure;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Closure $next \
     * @param array|string[] $guards
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
     * @internal param null $guard
     */
    public function handle($request, Closure $next, ...$guards)
    {
        if (isset($guards[0]) && $guards[0] == 'admin') {
            if(!Auth::guard($guards[0])->check()) {
                return redirect('admin/login');
            }
        }
        if (!Auth::guard()->check() &&
            $request->is('comment')) {
            return redirect('login');
        }

        return $next($request);
    }
}
