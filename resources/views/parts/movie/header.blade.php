<header class="head-box clearfix" id="header-top">
    <div class="container-xl">
        <div class="app-header clearfix">
            <h1 class="app-header__logo">
                <a class="pic_logo" href="/">
                    <img src="{{ small_logo() }}" alt="{{ siteName() }}">
                </a>
            </h1>
            <ul class="app-header__type">
                @include('parts.movie.header_menu')
            </ul>
            <ul class="app-header__menu">
                <li class="search">
                    <form class="search-form" name="search" method="get" action="/movie/search">
                        <input name="q" type="search" class="search-input" autocomplete="off"
                            placeholder="{{ isset($queryKeyword) ? $queryKeyword : '搜索想看的' }}">
                        <button class="search-submit" id="searchbutton" type="submit" name="submit">
                            <i class="iconfont icon-search"></i>
                        </button>
                    </form>
                </li>
                {{-- <li class="hide-xs">
                    <a href="javascript:;" title="留言反馈（暂未开放）" onclick="alert('敬请期待')">
                        <i class="iconfont icon-comments-fill"></i>
                    </a>
                </li> --}}
                @if (Auth::check())
                    <li class="hide-xs" title="播放记录" dropdown-target=".play-history" dropdown-toggle="hover">
                        <a href="javascript:;">
                            <i class="iconfont icon-clock-fill"></i>
                        </a>
                        <div class="dropdown-box play-history">
                            <div class="history-box clearfix">
                                <div class="ht-movie_list">
                                    <div class="video_headline">播放记录</div>
                                    @php
                                    $historyMovies
                                    =Auth::user()->movieHistory()->orderByDesc('updated_at')->take(10)->get();
                                    @endphp
                                    @foreach ($historyMovies as $historyItem)
                                        @include('parts.movie.history_movie_item')
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </li>
                @endif
                @if (Auth::check())
                    {{-- 已登录 TODO: 登录后的UI交互 --}}
                    <li title="个人中心" dropdown-target=".user-center" dropdown-toggle="hover">
                        <a href=" javascript:;"><i class="iconfont icon-usercenter"></i></a>
                        <div class="dropdown-box user-center">
                            <ul class="clearfix">
                                <li><a class="item" href="/movie/favorites?type=like">我的喜欢</a></li>
                                <li><a class="item" href="/movie/favorites?type=favorite">我的收藏</a></li>
                                <li><a class="item logout">退出登录</a></li>
                            </ul>
                        </div>
                    </li>
                @else
                    {{-- 未登录 TODO: 点击登录--}}
                    <li title="点击登录" data-toggle="modal" data-target="#login-modal">
                        <a href="javascript:;">
                            <i class="iconfont icon-account-fill"></i>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
    <div class="category_nav">
        <ul class="swipe_nav">
            @include('parts.movie.header_menu_drop',['item_class'=>'tab-item'])
        </ul>
        <div class="nav-arrow">
            <i class="iconfont icon-arrow-down"></i>
        </div>
    </div>

    {{-- 下拉顶部菜单 --}}
    @include('parts.movie.modal.nav_drawer')
</header>

