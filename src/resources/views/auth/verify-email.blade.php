{{-- resources/views/auth/verify.blade.php --}}
@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/verify.css') }}">
@endsection

@section('content')
<main class="verify" aria-labelledby="verify-title">
  <div class="verify__container">
    <h1 id="verify-title" class="verify__title">
      登録していただいたメールアドレスに認証メールを送付しました。<br>
      メール認証を完了してください。
    </h1>

    {{-- 大きなCTAボタン（参考画像どおり） --}}
    <a href="{{ route('verification.notice') }}"
       class="verify__cta"
       role="button"
       aria-label="認証手順へ">
       認証はこちらから
    </a>

    {{-- 再送リンク（小さめテキスト） --}}
    <form method="POST" action="{{ route('verification.send') }}" class="verify__resend" aria-label="認証メールを再送する">
      @csrf
      <button type="submit" class="verify__resend-link">認証メールを再送する</button>
    </form>

    {{-- 再送完了フラッシュ --}}
    @if (session('status') === 'verification-link-sent')
      <p class="verify__flash" role="status">認証メールを再送しました。数分待ってからご確認ください。</p>
    @endif

    @env('local')
      <p class="verify__hint">
        開発環境では <code>storage/logs/laravel.log</code> に認証URLが出力されます。<br>
        MailHog を使っている場合は <a href="http://localhost:8025" class="verify__hint-link">MailHog</a> でも確認できます。
      </p>
    @endenv
  </div>
</main>
@endsection
