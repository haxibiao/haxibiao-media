<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Movie;

trait MovieResolvers
{
    public function resolversCategoryMovie($root, $args, $content, $info)
    {
        $region     = data_get($args,'region');
        $type   = data_get($args,'type');
        $style   = data_get($args,'style');
        $country   = data_get($args,'country');
        $lang   = data_get($args,'lang');
        $year   = data_get($args,'year');

        $qb = Movie::orderByDesc("created_at");
        if($region){
            $qb = $qb->where('region', $region);
        }
        //按电影类型分类
        if($type){
            $qb = $qb->where('type', $type);
        }
        //按电影风格分类
        if($style){
            $qb = $qb->where('style', $style);
        }
        //按国家分类
        if($country){
            $qb = $qb->where('country', $country);
        }
        //按语言分类
        if($lang){
            $qb = $qb->where('lang', $lang);
        }
        //按语言分类
        if($year){
            $qb = $qb->where('year', $year);
        }
        return $qb;
    }

    public function resolversMovie($root, $args, $content, $info)
    {
        $movie = Movie::find(data_get($args,'movie_id'));
        app_track_event('看视频', '电影详情',data_get($args,'movie_id'));
        return $movie;
    }

    public function resolversRecommendMovie($root, $args, $content, $info)
    {
        return Movie::inRandomOrder()->take(7)->get(); 
    }

    public function getFilters(){
        return [
            [
                'id'            => 'region',
                'filterName'    => '剧种',
                'filterOptions' =>
                ['韩剧', '日剧', '美剧','港剧','泰剧'],
            ],
            [
                'id'            => 'country',
                'filterName'    => '地区',
                'filterOptions' =>
                ['全部', '美国', '香港', '韩国', '日本', '印度', '欧美', '泰国'],
            ],
            [
                'id'            => 'year',
                'filterName'    => '年份',
                'filterOptions' =>
                ['2020', '2019', '2018', '2017', '2016', '2015'],
            ],
            [
                'id'            => 'type',
                'filterName'    => '类型',
                'filterOptions' =>
                ['全部', '古装', '武侠', '都市', '悬疑', '言情', '喜剧'],
            ],
        ];
    }
}
