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
        $this->info('安装 media');

        $this->comment("复制 stubs ...");
        copyStubs(__DIR__, $force);

        $this->comment('安装数据库...');
        $this->call('migrate');

        $this->comment("发布 media");
        $this->call('media:publish', ['--force' => $force]);
    }

}
