<?php

namespace haxibiao\media;

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

        $this->comment("复制 stubs ...");

        copy($this->resolveStubPath('/stubs/Video.stub'), app_path('Video.php'));
        copy($this->resolveStubPath('/stubs/Image.stub'), app_path('Image.php'));

    }

}
