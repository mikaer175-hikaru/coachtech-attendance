{{-- resources/views/auth/verify.blade.php --}}
@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/verify.css') }}">
@endsection

@section('content')
<main class="verify" aria-labelledby="verify-title">
    <div class="verify__container">
        <h1 id="verify-title" class="verify__title">メール認証を完了してください</h1>

        <a href="{{ route('verification.notice') }}"
            class="verify__cta"
            role="button"
            aria-label="認証手順へ">
            認証はこちらから
        </a>

        {{-- 案内文 --}}
        <p class="verify__lead">
            登録いただいたメールアドレス宛に認証用リンクを送信しました。<br>
            メールに記載のリンクをクリックして、認証を完了してください。
        </p>

        {{-- 成功フラッシュ（再送後など） --}}
        @if (session('status') === 'verification-link-sent')
            <p class="verify__flash" role="status">認証メールを再送しました。数分待ってからご確認ください。</p>
        @endif

        {{-- 開発時のヒント（MAIL_MAILER=log のときの案内） --}}
        @env('local')
            <p class="verify__hint">
                開発環境では <code>storage/logs/laravel.log</code> に認証URLが出力されます。
            </p>
        @endenv

        {{-- 再送フォーム --}}
        <form method="POST" action="{{ route('verification.send') }}" class="verify__resend" aria-label="認証メールを再送する">
            @csrf
            <button type="submit" class="verify__button">認証メールを再送する</button>
        </form>

        {{-- 任意：ログアウト（別アカウントでやり直す時に便利） --}}
        <form method="POST" action="{{ route('logout') }}" class="verify__logout" aria-label="ログアウト">
            @csrf
            <button type="submit" class="verify__link">ログアウト</button>
        </form>
    </div>
</main>
@endsection
