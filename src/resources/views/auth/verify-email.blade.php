{{-- resources/views/auth/verify.blade.php --}}
@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/verify.css') }}">
@endsection

@section('content')
<main class="verify" aria-labelledby="verify-title">
    <div class="verify__container">
        <h1 id="verify-title" class="verify__title">メール認証を完了してください</h1>

        <p class="verify__lead">
            登録いただいたメールアドレス宛に認証用リンクを送信しました。<br>
            メールに記載のリンクをクリックして、認証を完了してください。
        </p>

        @if (session('status') === 'verification-link-sent')
            <p class="verify__flash" role="status">認証メールを再送しました。数分待ってからご確認ください。</p>
        @endif

        @env('local')
            <p class="verify__hint">
                開発環境では <code>storage/logs/laravel.log</code> に認証URLが出力されます。<br>
                MailHog を使っている場合は <a href="http://localhost:8025" class="verify__hint-link">MailHog</a> を開いてご確認ください。
            </p>
        @endenv

        {{-- 再送フォーム --}}
        <form method="POST" action="{{ route('verification.send') }}" class="verify__resend" aria-label="認証メールを再送する">
            @csrf
            <button type="submit" class="verify__button">認証メールを再送する</button>
        </form>
    </div>
</main>
@endsection
