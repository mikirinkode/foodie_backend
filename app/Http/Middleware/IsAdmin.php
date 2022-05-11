<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/*
    GANTI PAKE CLASS UserAsAdmin
*/
class IsAdmin
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
        // cek apakah user yang login adalah admin
        if (Auth::user() && Auth::user()->roles == 'ADMIN'){
            return $next($request);
        }

        // jika user bukan admin maka langsung diarahkan ke homepage
        return redirect('/');
    }
}