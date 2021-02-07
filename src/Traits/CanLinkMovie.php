<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\LinkMovie;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait CanLinkMovie
{
    public function linkMovies(): MorphMany
    {
        return $this->morphMany(LinkMovie::class, 'linked');
    }

    public function firstMovie()
    {
        return $this->linkMovies->first()->movie();
    }

    public function getMoviesAttribute()
    {
        $movies = [];
        foreach ($this->linkMovies as $linkMovie) {
            $movies[] = $linkMovie->movie;
        }
        return $movies;
    }

    public function toggleLink($movieIds, $linkedId, $linkedType)
    {
        foreach ($movieIds as $key => $movieId) {
            $link = LinkMovie::firstOrNew(
                [
                    'movie_id'    => $movieId,
                    'linked_id'   => $linkedId,
                    'linked_type' => $linkedType,
                ]

            );
            if (isset($link)) {

                $link->save();
            } else {
                $link->delete();
            }
        }
    }
}
