<?php
namespace haxibiao\media;

use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{
    public function boot()
    {

        //TODO: migrations

        //TODO: events
        Video::observe(Observers\VideoObserver::class);

    }

    public function register()
    {

    }

}
