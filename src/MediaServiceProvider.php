<?php
namespace haxibiao\media;

use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->commands([
            Console\InstallCommand::class,
            Console\PublishCommand::class,
        ]);
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__ . '/../config/media.php' => config_path('media.php'),
            ], 'media-config');

        }

        //TODO: migrations

        //TODO: events
        Video::observe(Observers\VideoObserver::class);

    }

}
