<div id="carouselHotMovies" class="carousel slide" data-ride="carousel">
    <ol class="carousel-indicators">
        @foreach ($hotMovies as $movie)
            @if ($loop->index < 1)
                <li class="active" data-target="#carouselHotMovies" data-slide-to="{{ $loop->index }}"></li>
            @else
                <li data-target="#carouselHotMovies" data-slide-to="{{ $loop->index }}"></li>
            @endif
        @endforeach
    </ol>
    <div class="carousel-inner">
        @foreach ($hotMovies as $movie)
            @if ($loop->index < 1)
                <div class="carousel-item active">
                    <a class="img-responsive" href="/movie/{{ $movie->id }}" target="_blank">
                        <img class="carousel-pic" src="{{ $movie->cover_url }}" alt="{{ $movie->name }}">
                    </a>
                </div>

            @else
                <div class="carousel-item">
                    <a class="img-responsive" href="/movie/{{ $movie->id }}" target="_blank">
                        <img class="carousel-pic" src="{{ $movie->cover_url }}" alt="{{ $movie->name }}">
                    </a>
                </div>
            @endif
        @endforeach
    </div>
    <a class="carousel-control-prev" href="#carouselHotMovies" role="button" data-slide="prev">
    </a>
    <a class="carousel-control-next" href="#carouselHotMovies" role="button" data-slide="next">
    </a>
</div>
