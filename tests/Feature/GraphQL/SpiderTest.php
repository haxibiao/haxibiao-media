<?php
namespace Tests\Feature\GraphQL;


use App\User;
use Haxibiao\Media\Spider;
use Haxibiao\Breeze\GraphQLTestCase;

class SpiderTest extends GraphQLTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->make([
            'api_token' => str_random(60),
            'ticket'    => 100,
            'account'   => rand(10000000000, 99999999999),
        ]);
    }

    /* --------------------------------------------------------------------- */
    /* ------------------------------- Mutation ---------------------------- */
    /* --------------------------------------------------------------------- */
    
    /**
     * 采集抖音视频里的分享地址
     * 
     * @group spider
     */
    // public function testResolveDouyinVideoMutation()
    // {
    //     //确保后面UT不重复
    //     $rows      = Spider::where('source_url', 'https://v.douyin.com/vruTta/')->delete();
    //     $query     = file_get_contents(__DIR__ . '/spider/ResolveDouyinVideo.gql');
    //     $variables = [
    //         'share_link' => "#在抖音，记录美好生活#美元如何全球褥羊毛？经济危机下，2万亿救市的深层动力，你怎么看？#经济 #教育#云上大课堂 #抖音小助手 https://v.douyin.com/vruTta/ 复制此链接，打开【抖音短视频】，直接观看视频！",
    //     ];
    //     $this->runGuestGQL($query, $variables, $this->getOneHeaders($this->user));
    // }

    /* --------------------------------------------------------------------- */
    /* ------------------------------- Query ------------------------------- */
    /* --------------------------------------------------------------------- */

    /**
     * 采集抖音视频的爬虫
     * 
     * @group spider
     */
    public function testSpidersQuery()
    {
        $query     = file_get_contents(__DIR__ . '/spider/SpidersQuery.gql');
        $variables = [
            "limit"  => 10,
            "offset" => 0,
        ];

        $this->runGQL($query, $variables);
    }

    public function getOneHeaders($user)
    {
        $headers = [
            'Authorization' => 'Bearer ' . 'BvsQolpkVH3EUMvjF4NOUTtbYcrbzuG196tVaN46qgUVV80nq1SgOoJkWxof',
        ];

        return $headers;
    }
}
