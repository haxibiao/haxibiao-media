<?php

namespace Haxibiao\Media\Tests\Feature\GraphQL;

use App\User;
use App\Video;
use App\Article;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class VideoTest extends GraphQLTestCase
{
    use DatabaseTransactions;

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
    /**
     * 视频刷奖励
     *
     * @group video
     * @group testVideoPlayRewardMutation
     */
    public function testVideoPlayRewardMutation()
    {
        $query     = file_get_contents(__DIR__ . '/Video/videoPlayRewardMutation.graphql');
        $variables = [
            'input'=>[
                'video_id'=> $this->video->id,
                'play_duration'=> 12.3,
                'video_ids'=> [$this->video->id],
            ]
        ];
        $this->startGraphQL($query, $variables, $this->getRandomUserHeaders($this->user));
    }

    /**
     * 工厂APP看视频赚钱部分详细文字描述用
     *
     * @group video
     * @group testQueryDetailQuery
     */
    public function testQueryDetailQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Video/queryDetailQuery.graphql');
        $variables = [];
        $this->startGraphQL($query, $variables);
    }

    /**
     * video详细信息
     *
     * @group video
     * @group testVideoQuery
     */
    public function testVideoQuery()
    {
        $query     = file_get_contents(__DIR__ . '/Video/videoQuery.graphql');
        $variables = [
            'id' => $this->video->id,
        ];

        $this->startGraphQL($query, $variables);
    }
}
