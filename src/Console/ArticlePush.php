<?php

namespace Haxibiao\Media\Console;

use App\Article;
use App\Category;
use Illuminate\Console\Command;

class ArticlePush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'article:push {--category=: 指定分类} {--id=: 开始ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'push网站的文章article到哈希云';

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
        $qb = Article::publish();
        //哈希云category
        $content_category = null;
        if ($category = $this->option('category')) {
            $category = Category::where('name', $category)->first();
            if ($category) {
                $qb = $qb->has('categories')->where('category_id', $category->id);
                //拿到哈希云分类对象
                $content_category = self::getContentCategory($category);
            } else {
                $this->error("\n没有该分类：" . $category);
            }
        }

        if ($start_id = $this->option('id')) {
            $qb = $qb->where('id', '>', $start_id);
        }

        echo "开始上传article数据\n";
        $count = 0;
        $qb->chunkById(100, function ($articles) use (&$count, $content_category) {
            foreach ($articles as $article) {
                echo "\n正在上传文章" . $article->title;
                //只处理纯文章，视频article不处理
                $content_article = \DB::connection('media')
                    ->table('articles')
                    ->where('title', $article->title)
                    ->first();
                if ($article->category) {
                    $content_category = self::getContentCategory($article->category);
                }

                if (empty($content_article)) {
                    \DB::connection('media')->table('articles')->insert([
                        'title'       => $article->title,
                        'description' => $article->description,
                        'body'        => $article->body,
                        'user_id'     => 1,
                        'category_id' => $content_category->id ?? null,
                        'cover_path'  => $article->cover_path,
                        'status'      => $article->status,
                        'source'      => env('APP_DOMAIN'),
                        'source_id'   => $article->id,
                        'json'        => json_encode($article->json),
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                    $count++;
                    echo "\n文章上传成功：" . $article->title;
                } else {
                    echo "\n文章已存在，跳过：" . $article->title;
                }
            }
        });
        echo "\n上传成功" . $count . "条文章数据";
    }

    public static function getContentCategory($category)
    {
        $content_category = \DB::connection('media')
            ->table('categories')
            ->where('name', $category->name)
            ->first();
        if (empty($content_category)) {
            $content_category = \DB::connection('media')
                ->table('categories')
                ->insert([
                    'name'        => $category->name,
                    "description" => $category->description,
                    "created_at"  => now(),
                    "updated_at"  => now(),
                ]);
            echo "\n创建分类成功:" . $category->name;
        }
        return $content_category = \DB::connection('media')
            ->table('categories')
            ->where('name', $category->name)
            ->first();

    }
}
