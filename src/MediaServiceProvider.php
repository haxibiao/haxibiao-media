<?php
/*
 * @Author: your name
 * @Date: 2020-12-11 10:26:43
 * @LastEditTime: 2020-12-11 10:28:28
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: /neihandianying.com/packages/haxibiao/media/src/MediaServiceProvider.php
 */

namespace Haxibiao\Media;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->commands([
            Console\InstallCommand::class,
            Console\ImageReFactoringCommand::class,
            Console\CountVideoViewsCommand::class,
            Console\FixVideoIDCommand::class,
            Console\MovieSync::class,
            Console\MoviePush::class,
        ]);
        $this->bindPathsInContainer();
    }

    public function boot()
    {
        // 更新视频的每日播放量
        $enabled = config('media.enabled_statistics_video_views', false);
        if ($enabled) {
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('haxibiao:video:CountVideoViewers')->dailyAt('1:30');;
            });
        }

        //注册路由
        $this->loadRoutesFrom(
            __DIR__ . '/../router.php'
        );

        if (! $this->app->configurationIsCached()) {
            $this->mergeConfigFrom(__DIR__.'/../config/database.php', 'database.connections');
        }

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
