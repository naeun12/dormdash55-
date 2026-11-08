<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AfterRegistrationMiddleware
{
    // I-check kung authenticated ba ang user sa dili pa ipadayon ang request.
    public function handle(Request $request, Closure $next): Response
    {
        // Kung wala naka-login ang user, ibalik siya sa registration page.
        if (!Auth::check()) {
            return redirect('/tenantRegister');
        }

        // Kung naka-login, ipadayon ang request ngadto sa sunod nga middleware o controller.
        return $next($request);
    }
}

