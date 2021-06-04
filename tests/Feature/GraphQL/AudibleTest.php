<?php

namespace Haxibiao\Media\Tests\Feature\GraphQL;

use App\Audible;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AudibleTest extends GraphQLTestCase
{
    use DatabaseTransactions;

    private $audibles;

    protected function setUp(): void
    {
        parent::setUp();
		$this->audibles = factory(Audible::class, 3)->create();
    }

	/**
	 * 听书详情
	 *
	 * @group  audible
	 * @group  testAudibleQuery
	 */
	public function testAudibleQuery()
	{
		$audibleId = data_get($this->audibles,'0.id');
		$query = file_get_contents(__DIR__ . '/Audible/audibleQuery.graphql');
		$variables = [
			'id' => $audibleId,
		];
		$this->startGraphQL($query, $variables);
	}

	/**
	 * 听书列表
	 *
	 * @group  audible
	 * @group  testFilterAudiblesQuery
	 */
	public function testFilterAudiblesQuery()
	{
		$query = file_get_contents(__DIR__ . '/Audible/filterAudiblesQuery.graphql');
		$this->startGraphQL($query);
	}
}
