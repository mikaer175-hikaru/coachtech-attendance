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
        return view('admin.auth.login');
    }

    public function login(AdminLoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        // ★ webガードでログインを統一
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'メールアドレスまたはパスワードが正しくありません。',
            ]);
        }

        // セッション固定攻撃対策
        $request->session()->regenerate();

        // ★ 管理者チェック（権限なければ即ログアウト）
        if (! $request->user()->is_admin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => '管理者権限がありません。',
            ]);
        }

        // メール未認証なら案内へ
        if (! $request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // 直前の intended か一覧へ
        return redirect()->intended(route('admin.attendance.list'))
            ->with('success', 'ログインしました');
    }

    public function logout()
    {
        Auth::logout(); // webガード
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('success', 'ログアウトしました');
    }
}
