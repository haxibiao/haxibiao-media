@php
$isSeries = $movie->count_series > 1
@endphp

<div class="movie-item">
    <a class="movie-thumb lazyload-img" href="/movie/{{ $movie->id }}" target="_blank" title="{{ $movie->name }}"
        style="background-image: url({{ $movie->cover_url }});">
        <span class="play-icon hidden-xs">
            <i class="iconfont icon-play-fill1"></i>
        </span>
        <span class="pic-tag pic-tag-top"
            style="background-color: {{ $isSeries ? '#5bb7fe' : '#f66d9b' }};">{{ mt_rand(7, 9) . '.' . mt_rand(1, 9) }}分</span>
        <span class="pic-text pic-bottom">{{ $isSeries ? '高清' : 'HD' }}</span>
    </a>
    <div class="movie-detail">
        <h4 class="title text-ellipsis">
            <a href="/movie/{{ $movie->id }}" target="_blank" title="{{ $movie->name }}">{{ $movie->name }}</a>
        </h4>
        <p class="text text-ellipsis hidden-xs">{{ $movie->actors ?? '主演： ' . $movie->actors }}</p>
    </div>
</div>
