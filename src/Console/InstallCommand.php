<?php

namespace Haxibiao\Media\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{

    /**
     * The name and signature of the Console command.
     *
     * @var string
     */
    protected $signature = 'media:install {--force}';

    /**
     * The Console command description.
     *
     * @var string
     */
    protected $description = '安装 haxibiao/media';

    /**
     * Execute the Console command.
     *
     * @return void
     */
    public function handle()
    {
        $force = $this->option('force');
        $this->info('发布 media');
        // $this->call('vendor:publish', ['--provider' => 'Haxibiao\Media\MediaServiceProvider', '--force']);
        // 兼容内涵电影，默认不强制发布gqls，后面可以单独vendor:publish --tag=media-graphql
        $this->call('vendor:publish', [
            '--tag'   => 'media-config',
            '--force' => $force,
        ]);

        $this->comment("复制 stubs ...");
        copyStubs(__DIR__, $force);

        $this->comment('迁移数据库变化...');
        $this->call('migrate');

    }

}
