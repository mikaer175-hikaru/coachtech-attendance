<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showForm()
    {
        // 既にadminログイン済みなら管理TOPへ
        if (auth('admin')->check()) {
            return redirect()->route('admin.attendance.list');
        }
        return view('admin.auth.login');
    }

    public function login(AdminLoginRequest $request)
    {
        $credentials = $request->only('email','password');

        // ★ adminガードでattempt
        if (! Auth::guard('admin')->attempt(
            $credentials,
            $request->boolean('remember')
        )) {
            throw ValidationException::withMessages([
                'email' => 'メールアドレスまたはパスワードが正しくありません。',
            ]);
        }

        $request->session()->regenerate();

        // 権限チェック（保険）
        if (! auth('admin')->user()->is_admin) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages([
                'email' => '管理者権限がありません。',
            ]);
        }

        // メール未認証なら案内へ
        if (! auth('admin')->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('admin.attendance.list'))
            ->with('success', 'ログインしました');
    }

    public function logout()
    {
        Auth::guard('admin')->logout(); // ★ adminガードでログアウト
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'ログアウトしました');
    }
}
