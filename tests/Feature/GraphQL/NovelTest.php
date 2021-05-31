<?php

namespace Haxibiao\Media\Tests\Feature\GraphQL;

use App\Novel;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NovelTest extends GraphQLTestCase
{
    use DatabaseTransactions;

    private $novels;

    protected function setUp(): void
    {
        parent::setUp();
		$this->novels = factory(Novel::class, 3)->create();
    }

	/**
	 * 听书详情
	 *
	 * @group  novel
	 * @group  testNovelQuery
	 */
	public function testNovelQuery()
	{
		$novelId = data_get($this->novels,'0.id');
		$query = file_get_contents(__DIR__ . '/Novel/novelQuery.graphql');
		$variables = [
			'id' => $novelId,
		];
		$this->startGraphQL($query, $variables);
	}

	/**
	 * 听书列表
	 *
	 * @group  novel
	 * @group  filterNovelsQuery
	 */
	public function testFilterNovelsQuery()
	{
		$query = file_get_contents(__DIR__ . '/Novel/filterNovelsQuery.graphql');
		$this->startGraphQL($query);
	}
}
