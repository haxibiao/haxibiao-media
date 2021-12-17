<div class="rc-item">
    <a href="/movie/{{ $relateMovie->id }}" target='_blank' class="cover-wrapper">
        <div class="common-lazy-img">
            <img src="{{ $relateMovie->cover_url }}" alt="">
        </div>
        <div class="video-mask"></div>
        <div class="duration">54:29:05</div>
    </a>
    <div class="info-wrapper">
        <a href="/movie/{{ $relateMovie->id }}" target='_blank' class="video-title webkit-ellipsis"
            title="{{ $relateMovie->name }}">
            {{ $relateMovie->name }}
        </a>
        <div class="video-count">
            {{ $relateMovie->actors }}
        </div>
    </div>
</div>
