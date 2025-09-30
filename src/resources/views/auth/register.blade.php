@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('content')
<main class="register">
    <div class="register__container">
        <h1 class="register__title">会員登録</h1>

        <form class="register__form" method="POST" action="{{ route('register.post') }}">
            @csrf

            <div class="register__field">
                <label class="register__label" for="name">お名前</label>
                <input class="register__input" type="text" name="name" id="name" value="{{ old('name') }}">
                @error('name') <p class="register__error">{{ $message }}</p> @enderror
            </div>

            <div class="register__field">
                <label class="register__label" for="email">メールアドレス</label>
                <input class="register__input" type="email" name="email" id="email" value="{{ old('email') }}">
                @error('email') <p class="register__error">{{ $message }}</p> @enderror
            </div>

            <div class="register__field">
                <label class="register__label" for="password">パスワード</label>
                <input class="register__input" type="password" name="password" id="password">
                @error('password') <p class="register__error">{{ $message }}</p> @enderror
            </div>

            <div class="register__field">
                <label class="register__label" for="password_confirmation">パスワード（確認用）</label>
                <input class="register__input" type="password" name="password_confirmation" id="password_confirmation">
                @error('password_confirmation') <p class="register__error">{{ $message }}</p> @enderror
            </div>

            <div class="register__actions">
                <button class="register__button" type="submit">登録する</button>
            </div>
        </form>

        <p class="register__link">
            すでにアカウントをお持ちの方は <a href="{{ route('login') }}">こちら</a>
        </p>
    </div>
</main>
@endsection
