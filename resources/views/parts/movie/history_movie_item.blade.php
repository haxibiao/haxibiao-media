@php
$movie = $historyItem->movie;
@endphp
@if ($movie)
@php
$isSeries = $movie->count_series > 1
@endphp
    <a class="hs-video_item" href="/movie/{{ $movie->id }}">
        <span class="video_figure">
            <img src="{{ $movie->cover_url }}" alt="" class="video_pic">
            @if ($isSeries)
                <div class="video_figure_caption">共{{ $movie->count_series }}集</div>
            @else
                <div class="video_figure_caption">HD</div>
            @endif
        </span>
        <span title="摩天大楼 第10集" class="video_title">{{ $movie->name }}</span>
        <span class="video_progress">{{ $historyItem->time_ago }}</span>
    </a>
@endif
