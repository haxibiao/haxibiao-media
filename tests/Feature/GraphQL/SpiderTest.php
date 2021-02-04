<?php

namespace Haxibiao\Media\Tests\Feature\GraphQL;

use App\User;
use Haxibiao\Media\Spider;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SpiderTest extends GraphQLTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * 采集抖音视频的爬虫
     *
     * @group spider
     * @group testSpidersQuery
     */
    public function testSpidersQuery()
    {
        $user = User::factory()->create();
        $headers = $this->getRandomUserHeaders($user);
        $query     = file_get_contents(__DIR__ . '/spider/SpidersQuery.graphql');
        $variables = [
            "limit"  => 10,
            "offset" => 0,
        ];
        $this->startGraphQL($query, $variables,$headers);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
