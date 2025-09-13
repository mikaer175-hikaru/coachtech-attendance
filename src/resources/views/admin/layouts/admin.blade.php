<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COACHTECH 管理画面</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/reset.css') }}">
    @yield('css')
</head>
<body>
    @include('admin.layouts.header')

    <main class="admin-main">
        @yield('content')
    </main>
</body>
</html>
