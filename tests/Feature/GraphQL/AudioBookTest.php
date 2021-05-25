<?php

namespace Haxibiao\Media\Tests\Feature\GraphQL;

use App\AudioBook;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AudioBookTest extends GraphQLTestCase
{
    use DatabaseTransactions;

    private $audioBooks;

    protected function setUp(): void
    {
        parent::setUp();
		$this->audioBooks = factory(AudioBook::class, 3)->create();
    }

	/**
	 * 听书详情
	 *
	 * @group  audioBook
	 * @group  testAudioBookQuery
	 */
	public function testAudioBookQuery()
	{
		$audioBookId = data_get($this->audioBooks,'0.id');
		$query = file_get_contents(__DIR__ . '/AudioBook/audioBookQuery.graphql');
		$variables = [
			'id' => $audioBookId,
		];
		$this->startGraphQL($query, $variables);
	}

	/**
	 * 听书列表
	 *
	 * @group  audioBook
	 * @group  filterAudioBooksQuery
	 */
	public function testFilterAudioBooksQuery()
	{
		$query = file_get_contents(__DIR__ . '/AudioBook/audioBookQuery.graphql');
		$this->startGraphQL($query);
	}
}
