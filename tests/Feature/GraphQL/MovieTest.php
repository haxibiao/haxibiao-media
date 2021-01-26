<?php

namespace Tests\Feature\GraphQL;

use Haxibiao\Breeze\GraphQLTestCase;
use Haxibiao\Breeze\User;
use Haxibiao\Media\Movie;

class MovieTest extends GraphQLTestCase
{

    /**
     * @group  movie
     * @group  testMovieQuery
     */
    public function testMovieQuery()
    {
        $query = file_get_contents(__DIR__ . '/movie/movieQuery.gql');
        $variables = [
            'movie_id' => 1,
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * @group  movie
     * @group  testRecommendMovieQuery
     */
    public function testRecommendMovieQuery()
    {
        $query = file_get_contents(__DIR__ . '/movie/recommendMovieQuery.gql');
        $variables = [
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * @group  movie
     * @group  testPostWithMoviesQuery
     */
    public function testPostWithMoviesQuery()
    {
        $query = file_get_contents(__DIR__ . '/movie/postWithMoviesQuery.gql');
        $variables = [
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * @group  movie
     * @group  testCategoryMovieQuery
     */
    public function testCategoryMovieQuery()
    {
        $query = file_get_contents(__DIR__ . '/movie/categoryMovieQuery.gql');
        //查询全部地区的电影
        $variables = [
            'region' => 'ALL',
        ];
        $this->startGraphQL($query, $variables);

        //查询2012年的美剧
        $variables = [
            'region' => 'MEI',
            'year' => '2012',
        ];
        $this->startGraphQL($query, $variables);

        //按热度排序2020年韩剧
        $variables = [
            'region' => 'HAN',
            'year' => '2020',
            'scopes' => 'HOT',
        ];
        $this->startGraphQL($query, $variables);

    }

    /**
     * @group  movie
     * @group  testSearchMovieQuery
     */
    public function testSearchMovieQuery()
    {
        $query = file_get_contents(__DIR__ . '/movie/searchMoviesQuery.gql');
        $keyword = Movie::first()->name;
        $variables = [
            'keyword' => $keyword,
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * @group  movie
     * @group  testSaveWatchProgressMutation
     */
    public function testSaveWatchProgressMutation()
    {
        $query = file_get_contents(__DIR__ . '/movie/saveWatchProgressMutation.gql');

        $userHeaders = $this->getRandomUserHeaders(User::first());
        $movie_id = Movie::first()->id;
        $variables = [
            'movie_id' => $movie_id,
            'series_index' => 0,
            'progress' => "100",
        ];
        $this->startGraphQL($query, $variables, $userHeaders);
    }

    /**
     * @group  movie
     * @group  testShowMovieHistoryQuery
     */
    public function testShowMovieHistoryQuery()
    {
        $query = file_get_contents(__DIR__ . '/movie/showMovieHistoryQuery.gql');
        $userHeaders = $this->getRandomUserHeaders(User::first());
        $variables = [
        ];
        $this->startGraphQL($query, $variables, $userHeaders);
    }

}
