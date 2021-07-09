<?php

namespace Haxibiao\Media\Console;

use App\EditorChoice;
use App\Stick;
use Haxibiao\Media\Movie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class StickSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stick:sync {--db : 数据库模式}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步电影数据到合集';

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
        if (!Schema::hasTable('movies')) {
            return $this->error("当前数据库 没有movies表!");
        }
        if ($this->option('db')) {
            $this->database();
        } else {
            dd("api方式先不支持");
        }
        return 0;
    }

    public function database()
    {
        //合集分类
        $qb = DB::connection('haxibiao')->table('editor_choices')->chunkById(100, function ($editorChoices) {
            foreach ($editorChoices as $editorChoice) {
                try {
                    $this->info("开始同步精选【{$editorChoice->title}】数据");
                    //创建精选
                    $editorChoice = EditorChoice::firstOrCreate([
                        'editor_id' => 1,
                        'title'     => $editorChoice->title,
                        'summary'   => $editorChoice->summary,
                    ]);

                    $movie_ids = DB::connection('haxibiao')->table('choiceables')
                        ->select('choiceable_id')
                        ->where('editor_choice_id', $editorChoice->id)
                        ->where('choiceable_type', 'movies')
                        ->pluck('choiceable_id')->toArray();

                    $movie_keys = DB::connection('haxibiao')->table('movies')
                        ->whereIn('id', $movie_ids)
                        ->pluck('movie_key')
                        ->toArray();
                    $this->info("获取同步电影movie_key成功...");

                    //获取movie_ids
                    $movie_ids = Movie::whereIn('movie_key', $movie_keys)->pluck('id')->toArray();
                    //保存关系
                    $editorChoice->movies()->sync($movie_ids);
                    $this->info("精选电影关系保存成功！！！");

                    $stick = DB::connection('haxibiao')
                        ->table('sticks')
                        ->where('editor_choice_id', $editorChoice->id)
                        ->first();
                    if ($stick) {
                        $this->info("开始同步置顶信息....");
                        $model = new Stick();
                        $model->forceFill(array_only((array) $stick, [
                            'editor_choice_id',
                            'place',
                            'rank',
                            'cover',
                            'editor_id',
                        ]))->save();
                    }

                } catch (\Exception $e) {
                    Log::error($e);
                    dd("同步异常！！！");
                }
            }
            $this->info("同步完成....");
        });
    }
}
