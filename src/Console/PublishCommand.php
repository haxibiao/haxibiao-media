<?php
namespace haxibiao\media\Console;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:publish {--force : 强制覆盖}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '发布 haxibiao-media';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('vendor:publish', [
            '--tag'   => 'media-config',
            '--force' => $this->option('force'),
        ]);
    }
}
