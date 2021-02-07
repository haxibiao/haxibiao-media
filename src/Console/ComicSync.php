<?php

namespace Haxibiao\Media\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ComicSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comic:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '从哈希云 sync 漫画到本地';

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
     * @return mixed
     */
    public function handle()
    {
        echo "开始拉取漫画数据 🚧";
        DB::connection('media')->table('comics')->chunkById(1000, function ($comics) {
            foreach ($comics as $comic) {

                DB::table("comics")->insert([
                    // 漫画名称
                    'name'       => $comic->name,
                    // 封面图地址
                    'cover'      => $comic->cover,
                    // 作者
                    'author'     => $comic->author,
                    // 创建时间
                    'created_at' => now(),
                    // 更新时间
                    'updated_at' => now(),
                ]);
                $this->info("已同步: " . $comic->name . "; 开始同步章节...");
                DB::connection('media')->table('comics_detail')->where('comic_id', $comic->id)->chunkById(1000, function ($comic_details) use (&$comic) {
                    if (!$comic_details->isEmpty()) {
                        foreach ($comic_details as $comic_detail) {
                            DB::table('comics_detail')->insert([
                                // 漫画顺序
                                'sort'          => $comic_detail->sort,
                                // Comics 表主键
                                'comic_id'      => $comic_detail->comic_id,
                                // 漫画章节名称
                                'chapter'       => $comic_detail->chapter,
                                // 图片地址
                                'url'           => $comic_detail->url,
                                // 缩略图地址
                                'thumbnail_url' => $comic_detail->thumbnail_url,
                                // 创建时间
                                'created_at'    => now(),
                                // 更新时间
                                'updated_at'    => now(),
                            ]);
                            $this->info($comic->name . " " . $comic_detail->chapter . " 同步成功");
                        }
                    }
                });
            }
        });
    }
}
