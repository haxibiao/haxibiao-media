<?php
namespace Tests\Feature\GraphQL;

use App\User;
use App\Video;
use App\Article;
use Haxibiao\Breeze\GraphQLTestCase;

class VideoTest extends GraphQLTestCase
{
    protected $user;
    protected $video;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory([
            'ticket'    => 100,
        ])->create();
        $this->video = Video::factory()->create();
        $this->article = Article::factory([
            'user_id' => $this->user->id,
            'status'  => 1,
            'video_id'  =>  $this->video->id,
        ])->create();

    }

    /* --------------------------------------------------------------------- */
    /* ------------------------------- Mutation ---------------------------- */
    /* --------------------------------------------------------------------- */

    /**
     * 视频刷奖励
     *
     * @group video
     * @group testVideoPlayRewardMutation
     */
    public function testVideoPlayRewardMutation()
    {
        $query     = file_get_contents(__DIR__ . '/video/videoPlayRewardMutation.gql');
        $variables = [
            'input'=>[
                'video_id'=> $this->video->id,
                'play_duration'=> 12.3,
                'video_ids'=> [$this->video->id],
            ]
        ];
        $this->runGuestGQL($query, $variables, $this->getRandomUserHeaders($this->user));
    }

    /* --------------------------------------------------------------------- */
    /* ------------------------------- Query ------------------------------- */
    /* --------------------------------------------------------------------- */

    /**
     * 工厂APP看视频赚钱部分详细文字描述用
     *
     * @group video
     * @group testQueryDetailQuery
     */
    public function testQueryDetailQuery()
    {
        $query     = file_get_contents(__DIR__ . '/video/queryDetailQuery.gql');
        $variables = [

        ];
        $this->runGQL($query, $variables);
    }

    /**
     * video详细信息
     *
     * @group video
     * @group testVideoQuery
     */
    public function testVideoQuery()
    {
        $query     = file_get_contents(__DIR__ . '/video/videoQuery.gql');
        $variables = [
            'id' => $this->video->id,
        ];

        $this->runGQL($query, $variables);
    }
}
