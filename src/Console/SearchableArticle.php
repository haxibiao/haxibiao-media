<?php

namespace Haxibiao\Media\Console;

use App\Article;
use Illuminate\Console\Command;
use MeiliSearch\Client;

class SearchableArticle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'searchable:article';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '手动将文章数据填充到 MeiliSearch 库';

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
        $qb        = Article::query()->whereNotNull('body');
        $masterKey = env('MEILISEARCH_KEY');
        $host      = env('MEILISEARCH_HOST');
        if (empty($masterKey)) {
            $this->error("请先在 .env 中补充 'MEILISEARCH_KEY' ");
            return;
        }
        if (empty($host)) {
            $this->error("请先在 .env 中补充 'MEILISEARCH_HOST' ");
            return;
        }
        $client    = new Client($host, $masterKey);
        $indexName = config('app.name') . '_article';
        $index     = $client->index($indexName);
        $qb->chunkById(1000, function ($articles) use (&$index) {
            $documents = [];
            foreach ($articles as $article) {
                $documents[] = [
                    'title' => $article->title,
                    'body'  => $article->body,
                    'id'    => $article->id,
                ];
            }
            $result   = $index->addDocuments($documents);
            $updateID = $result['updateId'];
            $this->info("update ID: $updateID");
        });
        return 0;
    }
}
