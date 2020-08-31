<?php

namespace Haxibiao\Media;

use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->commands([
            Console\InstallCommand::class,
            Console\ImageReFactoringCommand::class,
        ]);
        $this->bindPathsInContainer();
    }

    public function boot()
    {
        //注册路由
        $this->loadRoutesFrom(
            __DIR__ . '/../router.php'
        );

        //安装时需要
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__ . '/../config/media.php' => config_path('media.php'),
                __DIR__ . '/../config/vod.php'   => config_path('vod.php'),
            ], 'media-config');

            // 发布 graphql
            $this->publishes([
                __DIR__ . '/../graphql' => base_path('graphql'),
            ], 'media-graphql');

            // 发布 tests
            $this->publishes([
                __DIR__ . '/../tests/Feature/GraphQL' => base_path('tests/Feature/GraphQL'),
            ], 'media-tests');

            //注册 migrations paths
            $this->loadMigrationsFrom($this->app->make('path.haxibiao-media.migrations'));
        }
    }

    /**
     * Bind paths in container.
     *
     * @return void
     */
    protected function bindPathsInContainer()
    {
        foreach ([
            'path.haxibiao-media'            => $root = dirname(__DIR__),
            'path.haxibiao-media.config'     => $root . '/config',
            'path.haxibiao-media.graphql'    => $root . '/graphql',
            'path.haxibiao-media.database'   => $database = $root . '/database',
            'path.haxibiao-media.migrations' => $database . '/migrations',
            'path.haxibiao-media.seeds'      => $database . '/seeds',
        ] as $abstract => $instance) {
            $this->app->instance($abstract, $instance);
        }
    }
}
