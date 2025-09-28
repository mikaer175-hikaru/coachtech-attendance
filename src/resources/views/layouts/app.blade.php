<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COACHTECH 勤怠管理</title>
    <link rel="stylesheet" href="{{ asset('css/reset.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    {{-- 互換のため両方受ける --}}
    @yield('css')
    @yield('styles')
</head>
<body>
<header class="header">
    <div class="header__inner">
        <a class="header__logo" href="{{ url('/') }}">
            <img src="{{ asset('images/logo-white.png') }}" alt="COACHTECHロゴ">
        </a>

        @auth
            <nav class="header__nav" aria-label="メインメニュー">
                <ul class="header__menu">
                    {{-- 「勤怠」：存在するルートだけ表示して安全に --}}
                    @if (Route::has('attendance.index'))
                        <li class="header__item">
                            <a class="header__link" href="{{ route('attendance.index') }}">勤怠一覧</a>
                        </li>
                    @endif
                    @if (Route::has('attendance.create'))
                        <li class="header__item">
                            <a class="header__link" href="{{ route('attendance.create') }}">勤怠</a>
                        </li>
                    @endif

                    {{-- 申請一覧（ここが重要）--}}
                    <li class="header__item">
                        <a class="header__link" href="{{ route('stamp_requests.index') }}" aria-label="申請一覧へ">
                            <span aria-hidden="true">申請</span>
                        </a>
                    </li>

                    {{-- ログアウト --}}
                    <li class="header__item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="header__logout-button">ログアウト</button>
                        </form>
                    </li>
                </ul>
            </nav>
        @else
            {{-- 未ログイン時：会員登録／ログイン --}}
            <nav class="header__nav" aria-label="ゲストメニュー">
                <ul class="header__menu">
                    @if (Route::has('register'))
                        <li class="header__item"><a class="header__link" href="{{ route('register') }}">会員登録</a></li>
                    @endif
                    @if (Route::has('login'))
                        <li class="header__item"><a class="header__link" href="{{ route('login') }}">ログイン</a></li>
                    @endif
                </ul>
            </nav>
        @endauth
    </div>
</header>

<main class="content">
    @yield('content')
</main>
</body>
</html>
