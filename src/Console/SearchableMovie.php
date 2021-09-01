<?php

namespace Haxibiao\Media\Console;

use Haxibiao\Media\Movie;
use Illuminate\Console\Command;
use MeiliSearch\Client;

class SearchableMovie extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'searchable:movie';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '手动将影片数据填充到 MeiliSearch 库';

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
        $qb        = Movie::query()->whereNotNull('name');
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
        $client = new Client($host, $masterKey);
        $index  = $client->index(config('app.name'));
        $qb->chunkById(1000, function ($movies) use (&$index) {
            $documents = [];
            foreach ($movies as $movie) {
                $documents[] = [
                    'name' => $movie->name,
                    'id'   => $movie->id,
                ];
            }
            $result   = $index->addDocuments($documents);
            $updateID = $result['updateId'];
            $this->info("update ID: $updateID");
        });
        return 0;
    }
}
