<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Movie;
use Haxibiao\Media\MovieHistory;

trait MovieResolvers
{
    public function resolversCategoryMovie($root, $args, $content, $info)
    {
        $region = data_get($args,'region');
        //类型
        $type   = data_get($args,'type');
        //风格
        $style  = data_get($args,'style');
        $country= data_get($args,'country');
        //语言
        $lang   = data_get($args,'lang');
        $year   = data_get($args,'year');
        //排序规则
        $scopes   = data_get($args,'scopes');

        return Movie::when($region && $region != 'ALL', function ($qb) use ($region){
            return $qb->where('region', $region);
        })->when($type && $type != 'ALL', function ($qb) use ($type){
            return $qb->where('type', $type);
        })->when($style && $style != 'ALL', function ($qb) use ($style){
            return $qb->where('style', $style);
        })->when($country && $country != 'ALL', function ($qb) use ($country){
            return $qb->where('country', $country);
        })->when($lang && $lang != 'ALL', function ($qb) use ($lang){
            return $qb->where('lang', $lang);
        })->when($year && $year != 'ALL', function ($qb) use ($year){
            return $qb->where('year', $year);
        })->when($scopes && $scopes != 'ALL', function ($qb) use ($scopes){
            return $qb->orderbyDesc($scopes);
        });
    }

    public function resolversMovie($root, $args, $content, $info)
    {
        $movie = Movie::find(data_get($args, 'movie_id'));
        if(checkUser())
        {
            $user = getUser();
            // 保存观看历史
            MovieHistory::updateOrCreate([
                'user_id'  => $user->id,
                'movie_id' => $movie->id,
            ], [
                'last_watch_time' => now(),
            ]);
        }
        $movie->hits = $movie->hits + 1;
        $movie->save();
        app_track_event('看视频', '电影详情', data_get($args, 'movie_id'));
        return $movie;
    }

    public function resolversRecommendMovie($root, $args, $content, $info)
    {
        $count = data_get($args, 'count', 7);
        if (checkUser()) {
            $user = getUser();
            //收藏过的电影类型
            $movies_ids = $user->favoritedMovie()->pluck('faved_id')->toArray();
            $regions = Movie::whereIn('id', $movies_ids)->pluck('region')->toArray();
            $movies = Movie::inRandomOrder()
                ->whereIn('region', $regions)
                ->take($count)->get();
            $moviesCount = count($movies);
            if ($moviesCount <$count) {
                $random_movies = Movie::inRandomOrder()->take($count-$moviesCount)->get();
                $movies=array_merge($movies->toArray(),$random_movies->toArray());
            }
            return $movies;
        } else {
            return Movie::inRandomOrder()->take($count)->get();
        }
    }

    public function getFilters()
    {
        return [
            [
                'id' => 'scopes',
                'filterName' => '排序选项',
                'filterOptions' =>
                ['全部','最新','最热','评分'],
                'filterValue' =>
                ['ALL','NEW','HOT','SCORE']
            ],
            [
                'id' => 'region',
                'filterName' => '剧种',
                'filterOptions' =>
                ['全部','韩剧','日剧', '美剧', '港剧'],
                'filterValue' =>
                ['ALL','HAN', 'RI', 'MEI','GANG'],
            ],
            //此字段中数据为空,暂时不展示此过滤条件
            // [
            //     'id' => 'country',
            //     'filterName' => '地区',
            //     'filterOptions' =>
            //     ['全部', '美国', '香港', '韩国', '日本','印度', '欧美', '泰国'],
            //     'filterValue' =>
            //     ['ALL', '美国', '香港', '韩国', '日本','印度', '欧美', '泰国'],
            // ],
            [
                'id' => 'year',
                'filterName' => '年份',
                'filterOptions' =>
                ['全部', '2020', '2019', '2018', '2017', '2016'],
                'filterValue' =>
                ['ALL', '2020', '2019', '2018', '2017', '2016'],
            ],
            // [
            //     'id' => 'type',
            //     'filterName' => '类型',
            //     'filterOptions' =>
            //     ['全部', '古装', '武侠', '都市', '悬疑', '言情', '喜剧'],
            //     'filterValue' =>
            //     ['ALL', '古装', '武侠', '都市', '悬疑', '言情', '喜剧'],
            // ],
        ];
    }
}
