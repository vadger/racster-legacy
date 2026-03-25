<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role = null)
    {

        if (Auth::check()){

			if (Auth::user()->hasRole($role)){

				return $next($request);

			}else{

				return redirect(route('nouser'));

			}

        }

		return redirect(route('login'));

    }
}
