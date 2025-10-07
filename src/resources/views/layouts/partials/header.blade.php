@php
    $isAdmin = auth('admin')->check();
    $isUser  = auth()->check(); // webガード
@endphp

<header class="header" role="banner">
    <div class="header__inner">
        <a class="header__logo" href="{{ auth('admin')->check() ? route('admin.attendance.list') : url('/') }}">
            <img src="{{ asset('images/logo.svg') }}" alt="COACHTECHロゴ">
        </a>

        @if(auth('admin')->check() || auth()->check())
            <nav class="header__nav" aria-label="メインメニュー">
                <ul class="header__menu">
                    @if(auth('admin')->check())
                        <li class="header__item">
                            <a class="header__link {{ request()->routeIs('admin.attendance.list') ? 'header__link--active' : '' }}"
                               href="{{ route('admin.attendance.list') }}"
                               aria-current="{{ request()->routeIs('admin.attendance.list') ? 'page' : 'false' }}">
                                勤怠一覧
                            </a>
                        </li>
                        <li class="header__item">
                            <a class="header__link {{ request()->routeIs('admin.staff.list') ? 'header__link--active' : '' }}"
                               href="{{ route('admin.staff.list') }}"
                               aria-current="{{ request()->routeIs('admin.staff.list') ? 'page' : 'false' }}">
                                スタッフ一覧
                            </a>
                        </li>
                        <li class="header__item">
                            <a class="header__link {{ request()->routeIs('stamp_requests.index') ? 'header__link--active' : '' }}"
                               href="{{ route('stamp_requests.index') }}"
                               aria-current="{{ request()->routeIs('stamp_requests.index') ? 'page' : 'false' }}">
                                申請一覧
                            </a>
                        </li>
                        <li class="header__item">
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="header__logout-button">ログアウト</button>
                            </form>
                        </li>
                    @else
                        <li class="header__item">
                            <a class="header__link {{ request()->routeIs('attendance.create') ? 'header__link--active' : '' }}"
                               href="{{ route('attendance.create') }}"
                               aria-current="{{ request()->routeIs('attendance.create') ? 'page' : 'false' }}">
                                勤怠
                            </a>
                        </li>
                        <li class="header__item">
                            <a class="header__link {{ request()->routeIs('attendance.list') ? 'header__link--active' : '' }}"
                               href="{{ route('attendance.list') }}"
                               aria-current="{{ request()->routeIs('attendance.list') ? 'page' : 'false' }}">
                                勤怠一覧
                            </a>
                        </li>
                        <li class="header__item">
                            <a class="header__link {{ request()->routeIs('stamp_requests.index') ? 'header__link--active' : '' }}"
                               href="{{ route('stamp_requests.index') }}"
                               aria-current="{{ request()->routeIs('stamp_requests.index') ? 'page' : 'false' }}">
                                申請
                            </a>
                        </li>
                        <li class="header__item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="header__logout-button">ログアウト</button>
                            </form>
                        </li>
                    @endif
                </ul>
            </nav>
        @endif
    </div>
</header>
