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
    protected $signature = 'media:install';

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
        $this->info('发布 media');
        $this->call('vendor:publish', ['--provider' => 'Haxibiao\Media\MediaServiceProvider', '--force']);

        $this->comment("复制 stubs ...");
        copy(__DIR__ . '/../../routes/api/media.php', base_path('routes/api/media.php'));
        copy(__DIR__ . '/stubs/Video.stub', app_path('Video.php'));
        copy(__DIR__ . '/stubs/Image.stub', app_path('Image.php'));
        copy(__DIR__ . '/stubs/Spider.stub', app_path('Spider.php'));

        // $this->comment('迁移数据库变化...');
        // $this->callSilent('migrate');

    }

}
