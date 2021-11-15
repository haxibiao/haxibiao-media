<?php

namespace Haxibiao\Media\Console;

use App\Movie;
use App\Stick;
use App\EditorChoice;
use Haxibiao\Content\Traits\Choiceable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncMovieCollect extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:movieCollect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步剧好看片单数据';

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
     * 只是同步剧好看上的推荐->片单->影片的关系数据，不支持影片剧集的最新操作
     * @return int
     */
    public function handle()
    {
        DB::connection('juhaokan')->table('sticks')->orderBy('id','asc')->chunk(1000,function($sticks){
            foreach($sticks as $stick){ 
                //推荐片单
                $editorChoice = DB::connection('juhaokan')->table('editor_choices')->find($stick->editor_choice_id);

                //精选片单
                $movieIds = DB::connection('juhaokan')->table('choiceables')->where('editor_choice_id',$stick->editor_choice_id)->pluck('choiceable_id')->toArray();
                $movieKeys   = DB::connection('juhaokan')->table('movies')->whereIn('id',$movieIds)->pluck('movie_key')->toArray();

                //保存数据
                $editorChoiceInfo = EditorChoice::updateOrCreate([
                    'title'         => $editorChoice->title,
                ],[
                    'rank'          => $editorChoice->rank,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                    'summary'       => $editorChoice->summary,
                    'editor_id'     => $editorChoice->editor_id,
                ]);

                Stick::updateOrCreate([
                    'place'             => $stick->place,
                    'editor_choice_id'  => $editorChoiceInfo->id,
                    'app_name'          => $stick->app_name,
                ],[
                    'rank'          => $stick->rank,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                    'editor_id'     => rand(1,3)
                ]);

                $movies = Movie::whereIn('movie_key',$movieKeys)->get();
                $count = 0;
                foreach($movies as $movie)
                {
                    Choiceable::updateOrCreate([
                        'editor_choice_id'  => $editorChoiceInfo->id,
                        'choiceable_type'   => $movie->getMorphClass(),
                        'choiceable_id'     => $movie->id,
                    ],[
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                    $count++;
                }
                $this->info("引入剧好看片单成功，该片单为: $stick->place, 该片单推荐的影片有: $count 个");
            }
        });
    } 
}
