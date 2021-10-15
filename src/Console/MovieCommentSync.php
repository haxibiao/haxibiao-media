<?php

namespace Haxibiao\Media\Console;

use App\Comment;
use App\User;
use Haxibiao\Media\Movie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * 同步内涵云长视频评论数据
 */
class MovieCommentSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'moviecomment:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步最新mediachain电影评论';

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

        $this->api();

        return 0;
    }

    public function api()
    {
        $success = 0;
        $fail    = 0;
        $total   = 0;
        $page    = 0;

        $returnCount = 0;
        do {
            data_set($args, 'page', $page);
            $requestArgs = http_build_query($args);
            $url         = get_neihancloud_api() . "/api/movie/comments/?$requestArgs";
            $this->info("开始处理第 $requestArgs 页的数据");

            $result      = json_decode(file_get_contents($url), true);
            $returnCount = count($result['data']);
            if ($result['status'] == 200) {
                $comments = $result['data'];
                foreach ($comments as $comment) {
                    try {
                        $comment = self::SaveComment($comment);
                        $this->info("保存成功，Comment ID: $comment->id , body: $comment->body");
                    } catch (\Exception$exception) {
                        $this->error("同步影评失败了。。" . $comment['content']);
                        $fail++;
                        continue;
                    }
                    $total++;
                }
                $page++;
            }
        } while ($returnCount >= 300);
        $this->info('共检索出' . $total . '条评论,成功导入：' . $success . '部,失败：' . $fail . '部');

    }

    public static function SaveComment($comment)
    {
        $movie = Movie::where('movie_key', $comment['movie_id'])->first();
        if (empty($movie)) {
            //影片为空，跳过即可
            return;
        }

        $user = User::where([
            'name'   => $comment['user_name'],
            'avatar' => $comment['user_avatar'],
        ])->first();
        if (!$user) {
            $user = User::createUser($comment['user_name'], null, null);
            $user->update(['avatar' => $comment['user_avatar']]);
        }

        return Comment::firstOrCreate([
            'commentable_type' => 'movies',
            'commentable_id'   => $movie->id,
            'body'             => $comment['content'],
        ], [
            'user_id'    => $user->id,
            'status'     => Comment::PUBLISH_STATUS,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
