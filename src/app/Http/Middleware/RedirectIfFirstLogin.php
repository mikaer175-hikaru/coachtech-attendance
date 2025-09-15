<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectIfFirstLogin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->is_first_login) {
            return redirect()->route('user.setting');
        }

        return $next($request);
    }
}
