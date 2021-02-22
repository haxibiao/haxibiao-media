<?php

namespace Haxibiao\Media\Traits;

use App\User;
use Haxibiao\Breeze\Exceptions\GQLException;
use Haxibiao\Dimension\Dimension;
use Haxibiao\Helpers\utils\FFMpegUtils;
use Haxibiao\Media\Movie;
use Haxibiao\Media\SearchLog;
use Illuminate\Support\Facades\Storage;

trait MovieResolvers
{
    public function resolversCategoryMovie($root, $args, $content, $info)
    {
        $region = data_get($args, 'region');
        //类型
        $type = data_get($args, 'type');
        //风格
        $style   = data_get($args, 'style');
        $country = data_get($args, 'country');
        //语言
        $lang = data_get($args, 'lang');
        $year = data_get($args, 'year');
        //排序规则
        $scopes = data_get($args, 'scopes');

        return Movie::when($region && $region != 'ALL', function ($qb) use ($region, $scopes) {
            if ($scopes && $scopes != 'ALL') {
                return $qb->where('region', $region);
            }
            return $qb->where('region', $region)->orderbyDesc('year');;
        })->when($type && $type != 'ALL', function ($qb) use ($type) {
            return $qb->where('type', $type);
        })->when($style && $style != 'ALL', function ($qb) use ($style) {
            return $qb->where('style', $style);
        })->when($country && $country != 'ALL', function ($qb) use ($country) {
            return $qb->where('country', $country);
        })->when($lang && $lang != 'ALL', function ($qb) use ($lang) {
            return $qb->where('lang', $lang);
        })->when($year && $year != 'ALL', function ($qb) use ($year) {
            return $qb->where('year', $year);
        })->when($scopes && $scopes != 'ALL', function ($qb) use ($scopes) {
            return $qb->orderbyDesc($scopes);
        });
    }

    public function resolversMovie($root, $args, $content, $info)
    {
        $movie = Movie::withoutGlobalScopes()->find(data_get($args, 'movie_id'));
        if (isset($movie)) {
            $movie->hits = $movie->hits + 1;
            $movie->save();
            app_track_event('看视频', '电影详情', data_get($args, 'movie_id'));
            //可播放资源或者收藏夹资源
            if ($movie->status == Movie::PUBLISH || $movie->favorited) {
                return $movie;
            }
        }
        return null;

    }

    public function resolversRecommendMovie($root, $args, $content, $info)
    {
        $count = data_get($args, 'count', 7);
        if (checkUser()) {
            $user = getUser();
            //收藏过的电影类型
            $movies_ids = $user->favoritedMovie()->pluck('favorable_id')->toArray();
            $regions    = Movie::whereIn('id', $movies_ids)->pluck('region')->toArray();
            $movies     = Movie::inRandomOrder()
                ->whereIn('region', $regions)
                ->take($count)->get();
            $moviesCount = count($movies);
            if ($moviesCount < $count) {
                $random_movies = Movie::inRandomOrder()->take($count - $moviesCount)->get();
                $movies        = array_merge($movies->toArray(), $random_movies->toArray());
            }
            return $movies;
        } else {
            return Movie::inRandomOrder()->take($count)->get();
        }
    }
    public function resolversSearchMovie($root, $args, $content, $info)
    {
        $keyword = data_get($args, 'keyword');
        app_track_event('电影', '搜索电影', $keyword);
        return static::search($keyword);

    }

    public function getFilters()
    {
        return [
            [
                'id'            => 'scopes',
                'filterName'    => '排序选项',
                'filterOptions' =>
                ['全部', '最新', '最热', '评分'],
                'filterValue'   =>
                ['ALL', 'NEW', 'HOT', 'SCORE'],
            ],
            [
                'id'            => 'region',
                'filterName'    => '剧种',
                'filterOptions' =>
                ['全部', '韩剧', '日剧', '美剧', '港剧'],
                'filterValue'   =>
                ['ALL', 'HAN', 'RI', 'MEI', 'GANG'],
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
                'id'            => 'year',
                'filterName'    => '年份',
                'filterOptions' =>
                ['全部', '2020', '2019', '2018', '2017', '2016'],
                'filterValue'   =>
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

    //通用movies查询接口
    public function resolveMovies($root, array $args, $context, $info)
    {
        $user_id = $args['user_id'] ?? null;
        $status  = $args['status'] ?? null;
        $keyword = $args['keyword'] ?? null;

        $qb = Movie::publish();
        if ($user_id) {
            $user = \App\User::find($user_id);
            if ($user) {
                $qb = $qb->where('user_id', $user->id);
            }
        }
        if ($status) {
            $qb = $qb->where('status', $status);
        }

        if ($keyword) {
            app_track_event('长视频', '搜索长视频', $keyword);
            Dimension::track("长视频搜索数", 1, "长视频");
            $qb = $qb->where('name', 'like', '%' . $keyword . '%');
            if (checkUser()) {
                if ($qb->count() > 0) {
                    Dimension::track("长视频搜索成功数", 1, "长视频");
                }
                $log = SearchLog::saveSearchLog($keyword, getUserId());
            }
        }

        return $qb;
    }

    // 韩剧星球，高甜榜单接口
    public function sweetyRankList()
    {
        return Movie::hanju()->latest('rank');
    }

    public function getSharePciture($rootValue, array $args, $context, $resolveInfo)
    {
        $movie = Movie::find($args['id']);
        throw_if(is_null($movie), GQLException::class, '该电影或电视剧不存在哦~,请换一个试试吧');
        if (empty($movie->data[0]) || empty($movie->data[0]->url)) {
            throw new GQLException("该视频资源已丢失,请稍后再试");
        }
        $covers = [];
        for ($i = 1; $i <= 3; $i++) {

            //如果在cos桶里有这个剧的截图就不重新截图了，直接返回。
            $file_name  = "movie_cover_{$movie->id}_{$i}";
            $cover_name = "storage/app/screenshot/" . $file_name . '.jpg';
            if (!is_prod_env()) {
                $cover_name = 'temp/' . $cover_name;
            }
            $exist = Storage::cloud()->exists($cover_name);
            if ($exist) {
                $covers[] = cdnurl($cover_name);
            } else {
                $covers[] = FFMpegUtils::saveCover($movie->data[0]->url, random_int((10 * $i), (50 * $i)), $file_name);
            }
        }

        return ["title" => "我正在追,推荐你的一定要看完哦~", "covers" => $covers];
    }
}
