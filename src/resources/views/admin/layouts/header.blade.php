<header class="admin-header">
    <div class="admin-header__inner">
        <a href="{{ route('admin.attendance.list') }}" class="admin-header__logo">
            <img src="{{ asset('images/logo-white.png') }}" alt="COACHTECHロゴ" class="admin-header__logo-img">
        </a>

        @auth('admin')
            <nav class="admin-header__nav">
                <a href="{{ route('admin.attendance.list') }}" class="admin-header__link">勤怠一覧</a>
                <a href="{{ route('admin.staff.list') }}" class="admin-header__link">スタッフ一覧</a>

                <form method="POST" action="{{ route('admin.logout') }}" class="admin-header__logout-form">
                    @csrf
                    <button type="submit" class="admin-header__logout-button">ログアウト</button>
                </form>
            </nav>
        @endauth
    </div>
</header>
