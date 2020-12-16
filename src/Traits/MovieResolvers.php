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
        return $qb;
    }

    public function resolversMovie($root, $args, $content, $info)
    {
        $movie = Movie::find(data_get($args,'movie_id'));
        app_track_event('看视频', '电影详情',data_get($args,'movie_id'));
        return $movie;
    }
}
