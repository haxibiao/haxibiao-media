<?php

namespace Haxibiao\Media\Console;

use App\Article;
use Illuminate\Console\Command;

class ArticleSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'article:sync {--domain= : 来源网站} {--category= : 指定分类} {--id= : 开始ID}';

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

        if ($start_id = $this->option('id')) {
            $qb = $qb->where('id', '>', $start_id);
        }

        echo "开始同步\n";
        $count = 0;
        $qb->chunkById(100, function ($articles) use (&$count, &$current_article_id) {
            foreach ($articles as $article) {
                echo "\n同步文章" . $article->title;
                $current_article_id = $article->id;
                //只处理纯文章，视频article不处理
                $count += Article::firstOrCreate([
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
            }
        });

        echo "\n导入成功" . $count . "条文章数据";
        echo "\n最后导入的article ID为：" . $current_article_id;
    }
}
