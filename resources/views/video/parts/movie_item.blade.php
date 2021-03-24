<li class="col-xs-6 col-md-4 video">
    <h4>来自影片</h4>
  <div class="video-item vt">
    <div class="thumb" style="max-height: 120px; overflow-y: hidden;">
      <a href="/movie/{{ $movie->id }}"  >
        <img src="{{ $movie->cover_url }}" alt="{{ $movie->name }}">
        <i class="duration">
          {{-- 持续时间 --}}
          {{-- @sectominute($movie->duration) --}}
        </i>
      </a>
    </div>
    <ul class="info-list">
      <li class="video-title">
        <a href="/movie/{{ $movie->id }}"  >{{ $movie->name }}</a>
      </li>
      @if(config('content.show_video_plays'))
      <li>
        {{-- 播放量 --}}
        <p class="subtitle single-line">{{ $article->hits }}次播放</p>
      </li>
      @endif
    </ul>
  </div>
</li>
