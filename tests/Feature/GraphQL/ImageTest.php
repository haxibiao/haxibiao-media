<?php

namespace Haxibiao\Media\Tests\Feature\GraphQL;

use App\Image;
use App\User;
use Haxibiao\Breeze\GraphQLTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ImageTest extends GraphQLTestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }
    /**
     * 图片查询
     *
     * @group  image
     * @group  testImageQuery
     */
    public function testImageQuery()
    {
        $headers = $this->getRandomUserHeaders($this->user);
        $image = Image::factory()->create([
            'user_id' => $this->user->id
        ]);
        $query = file_get_contents(__DIR__ . '/Image/ImageQuery.graphql');
        $variables = [
            'id' => $image->id,
        ];
        $this->startGraphQL($query, $variables, $headers);
    }

    /**
     * 上传图片
     *
     * @group  image
     * @group  testUploadImageMutation
     */
    public function testUploadImageMutation()
    {
        $headers = $this->getRandomUserHeaders($this->user);
        $query = file_get_contents(__DIR__ . '/Image/UploadImageMutation.graphql');
        $variables = [
            'image' => [
                $this->getBase64ImageString(),
            ],
        ];
        $this->startGraphQL($query, $variables, $headers);
    }

    protected function tearDown(): void
    {
        // Clear File
        $this->user->forceDelete();
        parent::tearDown();
    }
}
