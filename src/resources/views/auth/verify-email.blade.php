{{-- resources/views/auth/verify.blade.php --}}
@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/verify.css') }}">
@endsection

@section('content')
<main class="verify" aria-labelledby="verify-title">
    <div class="verify__container">
        <h1 id="verify-title" class="verify__title">メール認証を完了してください</h1>
        <p class="verify__lead">登録していただいたメールアドレスに認証メールを送付しました。<br>メール認証を完了してください。</p>

        <a href="#" class="verify__cta" role="button" aria-label="認証手順へ">認証はこちらから</a>

        <form method="POST" action="{{ route('verification.send') }}" class="verify__resend">
            @csrf
            <button type="submit" class="verify__resend-link">認証メールを再送する</button>
        </form>
    </div>
</main>
@endsection
