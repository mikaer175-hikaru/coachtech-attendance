// app/Http/Middleware/EnsureAdmin.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('admin.login')->with('error', 'ログインしてください');
        }
        if (!($request->user()->is_admin ?? false)) {
            // 任意：403にする or 管理者ログインに飛ばす
            return abort(403, '管理者権限が必要です');
            // または：return redirect()->route('admin.login')->with('error', '管理者権限が必要です');
        }
        return $next($request);
    }
}
