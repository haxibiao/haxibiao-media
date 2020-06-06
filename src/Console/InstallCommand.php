<?php

namespace haxibiao\media\Console;

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
        $this->call('vendor:publish', [
            '--tag'   => 'media-config',
            '--force' => true,
        ]);

        $this->comment("复制 stubs ...");

        copy($this->resolveStubPath('/stubs/Video.stub'), app_path('Video.php'));
        copy($this->resolveStubPath('/stubs/Image.stub'), app_path('Image.php'));
        copy($this->resolveStubPath('/stubs/Spider.stub'), app_path('Spider.php'));

        //TODO: 数据库表结构问题还需要各项目自己比较修复字段差异先
        $this->info("数据库表结构问题还需要各项目自己比较修复字段差异先...");

    }

    protected function resolveStubPath($stub)
    {
        return __DIR__ . $stub;
    }

}
