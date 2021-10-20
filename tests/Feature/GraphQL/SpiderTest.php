<?php

namespace Haxibiao\Media\Tests\Feature\GraphQL;

use App\User;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SpiderTest extends GraphQLTestCase
{
    use DatabaseTransactions;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

    }

    /**
     * 采集抖音视频的爬虫
     * @group spider
     * @group testSpidersQuery
     */
    public function testSpidersQuery()
    {
        $headers = $this->getRandomUserHeaders($this->user);
        $query     = file_get_contents(__DIR__ . '/spider/SpidersQuery.graphql');
        $variables = [
            "limit"  => 10,
            "offset" => 0,
        ];
        $this->startGraphQL($query, $variables,$headers);
    }

    /**
     * 抖音解析接口
     * @group  spider
     * @group  testResolveDouyinVideo
     */
    public function testResolveDouyinVideo()
    {
        $user = $this->user;
        //确保后面UT不重复
        $headers      = $this->getRandomUserHeaders($user);
        $query     = file_get_contents(__DIR__ . '/Spider/resolveDouyinVideo.graphql');
        $variables = [
            'share_link' => "#在抖音，记录美好生活#美元如何全球褥羊毛？经济危机下，2万亿救市的深层动力，你怎么看？#经济 #教育#云上大课堂 #抖音小助手 https://v.douyin.com/vruTta/ 复制此链接，打开【抖音短视频】，直接观看视频！",
        ];

        $this->startGraphQL($query, $variables, $headers);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
