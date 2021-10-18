<?php

namespace Haxibiao\Media\Tests\Feature\GraphQL;

use App\Movie;
use App\MovieHistory;
use App\SeekMovie;
use Haxibiao\Breeze\GraphQLTestCase;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MovieTest extends GraphQLTestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $movie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'api_token' => str_random(60),
            'ticket'    => 100,
            'account'   => rand(10000000000, 99999999999),
        ]);

        $this->movie = Movie::factory()->create([
            'region' => '日剧',
            'year'   => '2011',
            'name'   => '日剧',
        ]);
        Movie::factory()->create([
            'region' => '韩剧',
            'year'   => '2013',
            'name'   => '韩剧',
        ]);
        Movie::factory()->create([
            'region' => '美剧',
            'year'   => '2012',
            'name'   => '美剧',
        ]);
        Movie::factory()->create([
            'region' => '美剧',
            'year'   => '2012',
            'name'   => '美剧',
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
     * @group  movie
     * @group  testMovieQuery
     */
    public function testMovieQuery()
    {
        $query = file_get_contents(__DIR__ . '/Movie/movieQuery.graphql');
        $variables = [
            'movie_id' => $this->movie->id,
        ];
        $this->startGraphQL($query, $variables);
    }

    /**
     * 我的求片记录
     * @group  movie
     * @group  testMySeekMovies
     */
    public function testMySeekMovies()
    {
        SeekMovie::factory(5)->create([
            'user_id'   => $this->user->id,
        ]);
        $query          = file_get_contents(__DIR__ . '/Movie/mySeekMoviesQuery.graphql');
        $headers        = $this->getRandomUserHeaders($this->user);
        $variables      = [
            'user_id'   => $this->user->id,
        ];
        $this->startGraphQL($query, $variables, $headers);
    }


    /**
     * 观影进度
     * note: 目前 haxibiao/media 中没有收录 Mutation 相关的 gql
     * @group  movie
     * @group  testSaveWatchProgressMutation
     */
    public function testSaveWatchProgressMutation()
    {
        $query = file_get_contents(__DIR__ . '/Movie/saveWatchProgressMutation.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'movie_id'      => $this->movie->id,
            'series_index'  => 1,
            'progress'      => 100,
        ];
        $this->startGraphQL($query, $variables, $headers);
    }

    /**
     * 观看历史记录
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
        $headers = $this->getRandomUserHeaders($this->user);
        $this->startGraphQL($query, [], $headers);
    }

    /**
     * 电影轮播图，热搜榜设置
     * @group  movie
     * @group  testActivitiesQuery
     */
    public function testActivitiesQuery()
    {
        $query = file_get_contents(__DIR__ . '/Movie/activitiesQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        //电影轮播图
        $variables = [
            'type'=>'SERIES'
        ];
        //热搜电影榜
        $this->startGraphQL($query, $variables, $headers);
        $variables = [
            'type'=>'SEARCH'
        ];
        $this->startGraphQL($query, $variables, $headers);
    }

    /**
     * 删除观看历史记录
     * @group movie
     * @group testDeleteMovieViewingHistoryMutation
     */
    public function testDeleteMovieViewingHistoryMutation()
    {
        $query = file_get_contents(__DIR__ . '/Movie/deleteMovieViewingHistoryMutation.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'movie_ids' => $this->movie->id,
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 魔法粘贴——查询影片名
     * @group movie
     * @group testFindMoviesQuery
     */
    public function testFindMoviesQuery()
    {
        $query = file_get_contents(__DIR__ .'/Movie/findMoviesQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'name' => $this->movie->name,
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 我的求片
     * @group movie
     * @group testMyReportMovieFixsQuery
     */
    public function testMyReportMovieFixsQuery()
    {
        $query = file_get_contents(__DIR__ .'/Movie/myReportMovieFixsQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'page' => 1,
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 影片的相关推荐
     * @group movie
     * @group testRelatedMoviesQuery
     */
    public function testRelatedMoviesQuery()
    {
        $query = file_get_contents(__DIR__ .'/Movie/relatedMoviesQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'movie_id' => $this->movie->id,
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 发布求片(上报影片修复)
     * @group movie
     * @group testReportMovieFixMutation
     */
    public function testReportMovieFixMutation()
    {
        $query = file_get_contents(__DIR__ .'/Movie/reportMovieFixMutation.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'movie_id' => $this->movie->id,
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 用户发布的所有长视频
     * @group movie
     * @group testSniffMoviesQuery
     */
    public function testSniffMoviesQuery()
    {
        $query = file_get_contents(__DIR__ .'/Movie/sniffMoviesQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [
            'page' => 1,
        ];
        $this->startGraphQL($query,$variables,$headers);
    }

    /**
     * 影视今日推荐
     * @group movie
     * @group testTodayRecommendQuery
     */
    public function testTodayRecommendQuery()
    {
        $query = file_get_contents(__DIR__ . '/Movie/todayRecommendQuery.graphql');
        $headers = $this->getRandomUserHeaders($this->user);
        $variables = [];
        $this->startGraphQL($query,$variables,$headers);
    }

    protected function tearDown(): void
    {
        $this->user->forceDelete();
        parent::tearDown();
    }
}
