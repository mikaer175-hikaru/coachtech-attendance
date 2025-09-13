{{-- resources/views/admin/auth/login.blade.php --}}
@extends('admin.layouts.admin')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-login.css') }}">

<div class="admin-login">
  <h1 class="admin-login__heading">管理者ログイン</h1>

  @if (session('error'))
    <p class="admin-login__flash admin-login__flash--error">{{ session('error') }}</p>
  @endif
  @if (session('success'))
    <p class="admin-login__flash admin-login__flash--success">{{ session('success') }}</p>
  @endif

  <form method="POST" action="{{ route('admin.login.submit') }}" class="admin-login__form" novalidate>
    @csrf

    <div class="admin-login__field">
      <label for="email" class="admin-login__label">メールアドレス <span class="admin-login__req">※</span></label>
      <input type="email" id="email" name="email" value="{{ old('email') }}" class="admin-login__input" required>
      @error('email') <p class="admin-login__error">{{ $message }}</p> @enderror
    </div>

    <div class="admin-login__field">
      <label for="password" class="admin-login__label">パスワード <span class="admin-login__req">※</span></label>
      <input type="password" id="password" name="password" class="admin-login__input" required>
      @error('password') <p class="admin-login__error">{{ $message }}</p> @enderror
    </div>

    <label class="admin-login__checkbox">
      <input type="checkbox" name="remember" value="1"> ログイン状態を保持する
    </label>

    <div class="admin-login__actions">
      <button type="submit" class="admin-login__button">ログイン</button>
    </div>
  </form>
</div>
@endsection
