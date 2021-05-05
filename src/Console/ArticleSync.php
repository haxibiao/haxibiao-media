<?php

namespace Haxibiao\Media\Console;

use App\Article;
use App\User;
use Illuminate\Console\Command;

class ArticleSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'article:sync {--domain= : 来源网站} {--type= : 类型｜article|diagrams} {--start_page= : 起始页} {--count_page= : 每页数量}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '获取哈希云的文章article';

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
     * @return mixed
     */
    public function handle()
    {

        $domain     = $this->option('domain') ?? "diudie.com";
        $type       = $this->option('type') ?? "diagrams";
        $start_page = $this->option('start_page') ?? 1;
        $count_page = $this->option('count_page') ?? 100;
        $userIds    = \App\User::where('role_id', '>', User::STATUS_ONLINE)->take(50)->inRandomOrder()->pluck('id')->toArray() ?? [User::first()->id];

        for ($start_page; $start_page < 1000; $start_page++) {
            $url    = \Haxibiao\Media\Video::getMediaBaseUri() . "api/articles?domain={$domain}&type={$type}&start_page={$start_page}&count_page={$count_page}";
            $result = json_decode(file_get_contents($url), true);
            $count  = 0;
            if (count($result)) {
                foreach ($result as $article) {
                    \DB::beginTransaction();
                    try {

                        $model = Article::firstOrNew([
                            'title' => $article['title'],
                        ]);
                        $model->forceFill(array_only($article, [
                            'description',
                            'body',
                            'type',
                            'cover_path',
                            'status',
                            'json',
                        ]));
                        $model->user_id = array_random($userIds);
                        $model->save();
                        $this->info($model->title . "   保存成功");
                        $count++;
                        \DB::commit();
                    } catch (\Exception $e) {
                        \DB::rollback();
                        $this->info($model->title . "   保存失败！！！");
                    }
                }
            }
        }
        $this->info("保存成功{$count}条数据");

    }
}
