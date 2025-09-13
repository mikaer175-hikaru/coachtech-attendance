@extends('layouts.app')

@section('content')
<main class="verify">
    <div class="verify__container">
        <h2>メール認証を完了してください</h2>
        <p>登録したメールアドレス宛に認証リンクを送信しました。</p>
        <p>メールをご確認いただき、リンクをクリックしてください。</p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit">再送信する</button>
        </form>
    </div>
</main>
@endsection
