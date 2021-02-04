<?php

namespace Haxibiao\Media\Console;

use App\Article;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ArticleSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'article:sync {--domain= : 来源网站} {--category= : 指定分类} {--start= : 开始ID} {--num= : 拉取数量}';

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

        $site     = $this->option('domain') ?? null;
        $category = $this->option('category') ?? null;
        $num      = $this->option('num') ?? null;

        $cache_key = env('APP_NAME') . 'article_sync';

        //记住最后同步的id
        $current_article_id = 0;
        $qb                 = \DB::connection('media')->table('articles');
        if ($site) {
            $qb = $qb->where('source', $site);
        }
        if ($category ?? null) {
            $category = \DB::connection('media')->table('categories')->where('name', $category)->first();
            if ($category) {
                $qb = $qb->where('category_id', $category->id);
            }
        }

        //指定从哪个id开始同步数据，不指定读缓存id
        $start_id = $this->option('start') ?? null;
        if (empty($start_id)) {
            $start_id = Cache::get($cache_key);
            if (empty($start_id)) {
                $start_id = 1;
                Cache::put($cache_key, $start_id);
            }
        }
        $qb = $qb->where('id', '>', $start_id);

        echo "开始同步\n";
        $count = 0;
        $qb->chunkById(100, function ($articles) use (&$count, &$num, &$cache_key, &$current_article_id) {
            foreach ($articles as $article) {
                echo "\n同步文章:" . $article->title;
                $current_article_id = $article->id;
                //缓存最后一次id
                Cache::put($cache_key, $current_article_id);

                //只处理纯文章，视频article不处理
                $result = Article::firstOrNew([
                    'title' => $article->title,
                ], [
                    'description' => $article->description,
                    'body'        => $article->body,
                    'user_id'     => 1,
                    'category_id' => $article->category_id,
                    'cover_path'  => $article->cover_path,
                    'status'      => $article->status,
                    'source'      => $article->source,
                    'json'        => $article->json,
                ]);
                if ($result->id) {
                    echo "\n该文章已存在跳过:" . $article->title;
                    continue;
                }
                $result->save();
                $count++;

                //达到指定同步数量退出
                if ($num) {
                    if ($count >= $num) {
                        echo "退出";
                        return false;
                    }
                }
            }
        });

        echo "\n导入成功" . $count . "条文章数据";
        echo "\n最后导入的article ID为：" . $current_article_id;
    }
}
