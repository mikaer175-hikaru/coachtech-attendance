<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showForm()
    {
        return view('admin.auth.login');
    }

    public function login(AdminLoginRequest $request)
    {
        $cred = [
            'email'    => $request->input('email'),
            'password' => $request->input('password'),
            'is_admin' => true,
        ];

        if (Auth::guard('admin')->attempt($cred, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('admin.attendance.list'))
                ->with('success', 'ログインしました');
        }

        return back()
            ->withInput($request->only('email'))
            ->with('error', 'ログイン情報が登録されていません');
    }

    public function logout()
    {
        if (auth('admin')->check()) {
            auth('admin')->logout();
        }
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('success', 'ログアウトしました');
    }
}
