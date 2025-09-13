<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COACHTECH 勤怠管理</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/reset.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @yield('css')
</head>
<body>
    <header class="header">
        <div class="header__logo">
            <img src="{{ asset('images/logo-white.png') }}" alt="COACHTECHロゴ">
        </div>
        <!-- ログイン中のみ表示 -->
        @auth
            <nav class="header__nav">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="header__logout-button">ログアウト</button>
                </form>
            </nav>
        @endauth
    </header>

    <div class="content">
        @yield('content')
    </div>
</body>
</html>
