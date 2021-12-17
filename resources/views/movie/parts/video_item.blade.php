<div class="video-item">
    <a href="/movie/{{ $video->id }}" target='_blank' class="cover-wrapper">
        <div class="common-lazy-img">
            <img src="{{ $video->cover_url }}" alt="">
        </div>
        <div class="video-mask"></div>
        <div class="duration" data-duration="{{ $video->duration }}">01:05</div>
    </a>
    <div class="info-wrapper">
        <a href="/movie/{{ $video->id }}" target='_blank' class="video-title webkit-ellipsis"
            title="{{ $video->introduction }}">
            {{ $video->introduction }}
        </a>
        <div class="video-count">
            {{ mt_rand(2, 100) }}万播放·{{ $video->like_count }}点赞
        </div>
    </div>
</div>
