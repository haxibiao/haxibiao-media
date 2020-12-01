<?php

namespace Haxibiao\Media\Console;

use Haxibiao\Media\Movie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MovieSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movie:sync {--region=} {--type=} {--style=} {--year=} {--producer=} {--actors=}';

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
        if (!Schema::hasTable('movies')){
            return $this->error("没有movies表");
        }

        $region     = $this->option('region');
        $type       = $this->option('type');
        $style      = $this->option('style');
        $year       = $this->option('year');
        $producer   = $this->option('producer');
        $actors     = $this->option('actors');

        $success = 0;
        $fail    = 0;
        $total   = 0;

        DB::connection('package_media_chain')->table('movies')
            ->when( $region , function ($q) use ($region){
                $q->where('region',$region);
            })->when( $type , function ($q) use ($type){
                $q->where('type',$type);
            })->when( $style , function ($q) use ($style){
                $q->where('style',$style);
            })->when( $year , function ($q) use ($year){
                $q->where('year',$year);
            })->when( $producer , function ($q) use ($producer){
                $q->where('producer',$producer);
            })->when( $actors , function ($q) use ($actors){
                $actors = explode(',',$actors);
                $q->where(function ($q)use($actors){
                    foreach ($actors as $actor){
                        if(!trim($actor)){
                            continue;
                        }
                        $q = $q->orWhere('actors','like','%'.$actor.'%');
                    }
                });
            })->orderBy('id')->chunk(100,function ($movies)use( &$fail,&$success,&$total){
                foreach ($movies as $movie){
                    $total++;
                    $movie = @json_decode(json_encode($movie), true);
                    DB::beginTransaction();
                    try {
                        $model = Movie::firstOrNew([
                            'name'=>data_get($movie,'name')
                        ]);
                        // 不同名的movie，直接导入
                        $model->forceFill(array_only($movie,[
                            'introduction',
                            'cover',
                            'producer',
                            'year',
                            'region',
                            'actors',
                            'miner',
                            'count_series',
                            'data',
                        ]))->save();
                        DB::commit();
                        $success++;
                        $this->info('已成功导入：'.$success.'部,当前导入:'.data_get($movie,'name'),'成功');
                    } catch (\Exception $ex) {
                        DB::rollback();
                        $fail++;
                        $this->info('已成功导入：'.$success.'部,当前导入:'.data_get($movie,'name'),'失败');
                    }
                }
            });
            $this->info('共检索出'.$total.'部电影,成功导入：'.$success.'部,失败：'.$fail.'部');
    }
}
