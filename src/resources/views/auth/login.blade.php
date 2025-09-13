@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="login">
    <div class="login__container">
        <div class="login__logo">
            <img src="{{ asset('images/logo.png') }}" alt="COACHTECHロゴ">
        </div>

        <h2 class="login__title">ログイン</h2>

        <form action="{{ route('login.submit') }}" method="POST" class="login__form">
            @csrf

            <div class="login__form-group">
                <label for="email" class="login__label">メールアドレス</label>
                <input type="email" name="email" class="login__input" value="{{ old('email') }}">
                @error('email')
                    <p class="login__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="login__form-group">
                <label for="password" class="login__label">パスワード</label>
                <input type="password" name="password" class="login__input">
                @error('password')
                    <p class="login__error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="login__button">ログインする</button>
        </form>

        <div class="login__link">
            <a href="{{ route('register') }}">会員登録はこちら</a>
        </div>
    </div>
</div>
@endsection
