<?php

namespace Tests\Feature\GraphQL;

use Haxibiao\Breeze\GraphQLTestCase;

class ImageTest extends GraphQLTestCase
{

    /* --------------------------------------------------------------------- */
    /* ------------------------------- Query ------------------------------- */
    /* --------------------------------------------------------------------- */

    /**
     * 图片查询
     *
     * @group  image
     */
    public function testImageQuery()
    {
        $token = $this->getRandomUser()->api_token;
        $query = file_get_contents(__DIR__ . '/image/ImageQuery.gql');
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
        $variables = [
            'id' => 1,
        ];
        $this->runGQL($query, $variables, $headers);
    }

    /* --------------------------------------------------------------------- */
    /* ------------------------------- Mutation ---------------------------- */
    /* --------------------------------------------------------------------- */

    /**
     * 上传图片
     *
     * @group  image
     */
    public function testUploadImageMutation()
    {
        $token = $this->getRandomUser()->api_token;
        $query = file_get_contents(__DIR__ . '/image/UploadImageMutation.gql');
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
        $variables = [
            'image' => [
                $this->getBase64ImageString(),
            ],
        ];
        $this->runGQL($query, $variables, $headers);
    }

}
