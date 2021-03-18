<?php
namespace Haxibiao\Media;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{

    public function register()
    {
        //帮助函数
        $src_path = __DIR__;
        foreach (glob($src_path . '/Helpers/*.php') as $filename) {
            require_once $filename;
        }

        //合并view paths
        if (!app()->configurationIsCached()) {
            $view_paths = array_merge(
                //APP 的 views 最先匹配
                config('view.paths'),
                //然后 匹配 breeze的默认views
                [media_path('resources/movie')]
            );
            config(['view.paths' => $view_paths]);
        }

        $this->commands([
            Console\InstallCommand::class,
            Console\PublishCommand::class,
            Console\ImageReFactoringCommand::class,
            Console\CountVideoViewsCommand::class,
            Console\FixVideoIDCommand::class,
            Console\MovieSync::class,
            Console\MoviePush::class,
            Console\VideoPush::class,
            Console\PostPush::class,
            Console\PostSync::class,
            Console\ArticlePush::class,
            Console\ArticleSync::class,
            Console\ComicSync::class,
            Console\ComicPush::class,
            Console\VideoSync::class,
            Console\PublishConfig::class,
            Console\CrawlDouyinVideos::class,
        ]);
        $this->bindPathsInContainer();

        $this->mergeConfigFrom(__DIR__ . '/../config/media.php', 'media');
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

        if (!app()->configurationIsCached()) {
            $this->mergeConfigFrom(__DIR__ . '/../config/database.php', 'database.connections');
            $this->mergeConfigFrom(__DIR__ . '/../config/disks.php', 'filesystems.disks');

            //media 默认cloud用cos
            config([
                'filesystems.default' => 'cos',
            ]);
        }

        //安装时需要
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__ . '/../config/media.php' => config_path('media.php'),
                __DIR__ . '/../config/vod.php'   => config_path('vod.php'),
            ], 'media-config');

            $this->publishes([
                __DIR__ . '/../config/applist.php' => config_path('applist.php'),
            ], 'media-applist');

            $this->publishes([
                __DIR__ . '/../graphql' => base_path('graphql/media'),
            ], 'media-graphql');

            $this->publishes([
                __DIR__ . '/../resources/images' => base_path('public/images'),
            ], 'media-resources');

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
