<?php

namespace Haxibiao\Media\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ComicPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comic:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'push漫画到哈希云';

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
     * note: 上传漫画排重, 仅仅依赖漫画的名称
     *
     * @return mixed
     */
    public function handle()
    {
        echo "开始上传漫画数据 🚧";
        DB::table("comics")->chunkById(1000, function ($comics){
            foreach($comics as $comic) {
                // 通过名称进行漫画排重
                $row = DB::connection('media')->table('comics')->select('id')->where('name', $comic->name)->get();
                if($row->isEmpty()) {
                    DB::connection('media')->table('comics')->insert([
                        // 漫画名称
                        'name' => $comic->name, 
                        // 封面图地址
                        'cover' => $comic->cover, 
                        // 作者
                        'author' => $comic->author,
                        // 创建时间
                        'created_at'  => now(),
                        // 更新时间
                        'updated_at'  => now(),
                    ]);
                    $this->info("已收纳：" . $comic->name . "; 开始上传具体章节...");
                    DB::table('comics_detail')->where('comic_id', $comic->id)->chunkById(1000, function($comic_details) use (&$comic){
                        if(!$comic_details->isEmpty()) {
                            foreach($comic_details as $comic_detail) {
                                DB::connection('media')->table('comics_detail')->insert([
                                    // 漫画顺序
                                    'sort' => $comic_detail->sort, 
                                    // Comics 表主键
                                    'comic_id' => $comic_detail->comic_id, 
                                    // 漫画章节名称
                                    'chapter' => $comic_detail->chapter,
                                    // 图片地址
                                    'url' => $comic_detail->url,
                                    // 缩略图地址
                                    'thumbnail_url' => $comic_detail->thumbnail_url,
                                    // 创建时间
                                    'created_at'  => now(),
                                    // 更新时间
                                    'updated_at'  => now(),
                                ]);
                                $this->info($comic->name . " " . $comic_detail->chapter . " 上传成功");
                            }
                        }
                    });
                }
            }
        });
    }
}
