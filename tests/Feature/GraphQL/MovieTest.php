<?php

namespace Haxibiao\Media\Tests\Feature\GraphQL;

use App\Movie;
use App\MovieHistory;
use App\Post;
use App\SeekMovie;
use Haxibiao\Breeze\GraphQLTestCase;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

class MovieTest extends GraphQLTestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'api_token' => str_random(60),
            'ticket'    => 100,
            'account'   => rand(10000000000, 99999999999),
        ]);
        Movie::factory()->create([
            'region' => '日剧',
            'year'   => '2011'
        ]);
        Movie::factory()->create([
            'region' => '韩剧',
            'year'   => '2013'
        ]);
        Movie::factory()->create([
            'region' => '美剧',
            'year'   => '2012'
        ]);
        Movie::factory()->create([
            'region' => '美剧',
            'year'   => '2012'
        ]);
    }
    /**
     * 电影分类
     *
     * @group  movie
     * @group  testCategoryMovieQuery
     */
    public function testCategoryMovieQuery()
    {
        $query = file_get_contents(__DIR__ . '/Movie/categoryMovieQuery.graphql');
        //查询全部地区的电影
        $variables = [
            'region' => 'ALL',
        ];
        $this->startGraphQL($query, $variables);

        //查询2012年的美剧
        $variables = [
            'region' => 'MEI',
            'year'   => '2012',
        ];
        $this->startGraphQL($query, $variables);

        //按热度排序2020年韩剧
        $variables = [
            'region' => 'HAN',
            'year'   => '2013',
            'scopes' => 'HOT',
        ];
        $this->startGraphQL($query, $variables);

    }

    /**
     * 筛选电影条件
     *
     * @group  movie
     * @group  testGetFiltersQuery
     */
    public function testGetFiltersQuery()
    {
        $query = file_get_contents(__DIR__ . '/Movie/getFiltersQuery.graphql');
        $this->startGraphQL($query);
    }

    /**
     *
     * @group  movie
     * @group  testMoviePosters
     */
    public function testMoviePosters()
    {
        $query = file_get_contents(__DIR__ . '/Movie/moviePosters.graphql');

        // 首页
        $variables = [
            'type' => 'INDEX',
        ];
        $this->startGraphQL($query, $variables);

        // 电视剧
        $variables = [
            'type' => 'SERIES',
        ];
        $this->startGraphQL($query, $variables);

        // 电影专题
        $variables = [
            'type' => 'THEME',
        ];
        $this->startGraphQL($query, $variables);

        // 搜索页展示
        $variables = [
            'type' => 'SEARCH',
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 电影详情
     *
     * @group  movie
     * @group  testMovieQuery
     */
    public function testMovieQuery()
    {
        $movie = Movie::factory()->create([
            'region' => '日剧',
            'year'   => '2011',
            'status' => 1
        ]);
        $query = file_get_contents(__DIR__ . '/Movie/movieQuery.graphql');
        $variables = [
            'movie_id' => $movie->id,
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 我的求片记录
     *
     * @group  movie
     * @group  testMySeekMovies
     */
    public function testMySeekMovies()
    {
        SeekMovie::factory(5)->create([
            'user_id' => $this->user->id,
        ]);
        $query       = file_get_contents(__DIR__ . '/Movie/mySeekMoviesQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables   = [
            'user_id' => $this->user->id,
        ];

        $this->startGraphQL($query, $variables, $headers);
    }

    /**
     * 电影推荐
     *
     * @group  movie
     * @group  testRecommendMovieQuery
     */
    public function testRecommendMovieQuery()
    {
        $query = file_get_contents(__DIR__ . '/Movie/recommendMovieQuery.graphql');

        // 登录
        $headers = $this->getRandomUserHeaders($this->user);
        $this->startGraphQL($query, [],$headers);

        // 未登录
        $headers = [];
        $this->startGraphQL($query, [],$headers);
    }

    /**
     * 观影进度
     *
     * note: 目前 haxibiao/media 中没有收录 Mutation 相关的 gql
     * @group  movie
     * @group  testSaveWatchProgressMutation
     */
    public function testSaveWatchProgressMutation()
    {
        $movie = Movie::factory()->create([
            'region' => '日剧',
            'year'   => '2011'
        ]);
        $query = file_get_contents(__DIR__ . '/Movie/saveWatchProgressMutation.graphql');

        $userHeaders = $this->getRandomUserHeaders($this->user);
        $variables = [
            'movie_id' => $movie->id,
            'series_index' => 1,
            'progress' => "100",
        ];
        $this->startGraphQL($query, $variables, $userHeaders);
    }

    /**
     * 观看历史记录
     *
     * @group  movie
     * @group  testShowMovieHistoryQuery
     */
    public function testShowMovieHistoryQuery()
    {
        $movie = Movie::factory()->create();
        $user  = User::factory()->create();
        MovieHistory::factory(5)->create([
            'movie_id' => $movie->id,
            'user_id'  => $user->id
        ]);
        $query = file_get_contents(__DIR__ . '/Movie/showMovieHistoryQuery.graphql');
        $userHeaders = $this->getRandomUserHeaders($this->user);
        $this->startGraphQL($query, [], $userHeaders);
    }


    /**
     * 搜索电影
     *
     * @group  movie
     * @group  testSearchMovieQuery
     */
    public function testSearchMovieQuery()
    {
        Movie::factory()->create([
            'name' => '独孤九剑'
        ]);
        $query = file_get_contents(__DIR__ . '/Movie/searchMoviesQuery.graphql');

        // 有搜索结果
        $variables = [
            'keyword' => '独孤',
        ];
        $this->startGraphQL($query, $variables);

        // 无搜索结果
        $variables = [
            'keyword' => Str::random(),
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 电影轮播图，热搜榜设置
     *
     * @group  movie
     * @group  testActivitiesMutation
     */
    public function testActivitiesMutation()
    {
        $query = file_get_contents(__DIR__ . '/Movie/activitiesMutation.graphql');
        $userHeaders = $this->getRandomUserHeaders($this->user);
        //电影轮播图
        $variables = [
            'type'=>'SERIES'
        ];
        //热搜电影榜
        $this->startGraphQL($query, $variables, $userHeaders);
        $variables = [
            'type'=>'SEARCH'
        ];
        $this->startGraphQL($query, $variables, $userHeaders);

    }

    protected function tearDown(): void
    {
        $this->user->forceDelete();
        parent::tearDown();
    }
}
