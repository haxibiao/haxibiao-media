<?php

namespace Haxibiao\Media\Console;

use Illuminate\Console\Command;

class MovieSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movie:sync {--region : 按区域导入最新mediachain电影} {--type=} {--style=} {--year=} {--producer=} {--actors=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步最新mediachain电影';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return 0;
    }
}
