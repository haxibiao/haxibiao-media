<?php

namespace Haxibiao\Media\Traits;

use App\User;
use Haxibiao\Breeze\Dimension;
use Haxibiao\Breeze\Exceptions\GQLException;
use Haxibiao\Content\Category;
use Haxibiao\Content\Post;
use Haxibiao\Helpers\utils\FFMpegUtils;
use Haxibiao\Media\Movie;
use Haxibiao\Media\MovieUser;
use Haxibiao\Media\SearchLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

trait MovieResolvers
{
    /**
     * 最新求片者
     */
    public function resolvelatestMovieFixReporters($root, $args, $content, $info)
    {
        return MovieUser::orderByDesc('created_at');
    }

    /**
     * 我发起的求片列表
     */
    public function resolveMyReportMovieFixs($root, $args, $content, $info)
    {
        if ($user = currentUser()) {
            // dd($user->findMovies());
            return MovieUser::where('user_id', $user->id)->orderByDesc('created_at');
        }
    }

    /**
     * 发起求片订单
     */
    public function resolveReportMovieFix($root, $args, $content, $info)
    {
        $movie_id = $args['movie_id'] ?? null; //求片的影片
        if ($movie_id && $movie = Movie::find($movie_id)) {
            if ($user = currentUser()) {

                //一个电影只能求一次，重复不处理
                MovieUser::firstOrCreate([
                    'user_id'  => $user->id,
                    'movie_id' => $movie->id,
                ]);
                return $movie;
            }
        }
    }

    /**
     * 粘贴片名查询电影列表
     */
    public function resolveFindMovies($root, $args, $content, $info)
    {
        $name = $args['name'] ?? '';
        $qb   = Movie::withoutGlobalScopes()->where('name', 'like', "{$name}%")->latest('id');
        return $qb->take(20)->get();
    }

    /**
     * 影片的相关推荐
     */
    public function resolveRelatedMovies($root, $args, $content, $info)
    {
        $first = $args['limit'] ?? 6;
        //不同类型的权重（名称,导演，演员）
        $rankNmae  = 3;
        $rankOther = 1;

        //查询依赖的movie对象
        $movie_id = $args['movie_id'] ?? 0;
        $movie    = Movie::findOrFail($movie_id);
        $movies   = collect([]);
        $qb       = Movie::latest('updated_at');

        //1.优先电影名匹配（xxx第一部 xxx第二部）
        $query = Movie::publish()
            ->where('id', '!=', $movie->id);

        $likeName = false;
        if (mb_strlen($movie->name) >= 6 || in_array(mb_substr($movie->name, -1), ['集', '季', '部'])) {
            $query    = $query->where('name', 'like', mb_substr($movie->name, 0, -3) . "%");
            $likeName = true;
        } else {
            if (is_numeric(mb_substr($movie->name, -1))) {
                $query    = $query->where('name', 'like', mb_substr($movie->name, 0, -1) . "%");
                $likeName = true;
            }
        }

        if ($likeName) {
            $similarMovies = $query->take($first)->get();
            foreach ($similarMovies as $similarMovie) {
                similar_text($movie->name, $similarMovie->name, $percent);
                //影片名字相似度百分之80-以上差不多就是第一部第二部的关系
                if ($percent > 80) {
                    $movies->push($similarMovie);
                    if (count($movies) >= $rankNmae) {
                        break;
                    }
                }
            }
        }

        // 2.优先同演员
        $actor = explode(",", $movie->actors)[0] ?? null;
        if (!blank($actor)) {
            $qb       = Movie::latest('updated_at');
            $qb_actor = $qb->where('actors', 'like', "%$actor%")
                ->where('id', '!=', $movie->id)
                ->whereNotIn('id', $movies->pluck('id')->toArray());
            if ($qb_actor->exists()) {
                $items  = $qb_actor->take($rankOther)->get();
                $movies = $movies->merge($items);
            }
        }
        // 3.再同导演
        if (!blank($movie->producer)) {
            $qb          = Movie::latest('updated_at');
            $qb_producer = $qb->where('producer', $movie->producer)
                ->where('id', '!=', $movie->id)
                ->whereNotIn('id', $movies->pluck('id')->toArray());
            if ($qb_producer->exists()) {
                $items  = $qb_producer->take($rankOther)->get();
                $movies = $movies->merge($items);
            }
        }
        // dd($movies->pluck('name')->toarray());

        if (count($movies) < $first) {
            // 4.同国家+同类型
            if (!empty($movie->country) || !empty($movie->type) || !empty($movie->region)) {
                $qb              = Movie::latest('updated_at');
                $qb_country_type = $qb
                    ->where('id', '<', $movie->id)
                    ->whereNotIn('id', $movies->pluck('id')->toArray())
                    ->where(function ($q) use ($movie) {
                        $q->orWhere('region', $movie->region)
                            ->orWhere('country', $movie->country)
                            ->orWhere('type', $movie->type);
                    });

                if ($qb_country_type->exists()) {
                    $items  = $qb_country_type->take(($first - count($movies)))->get();
                    $movies = $movies->merge($items);
                }
            }
        }

        return $movies;
    }

    /**
     * 个性推荐？(ivan: 仅电影图解)
     */
    public function resolveRecommendMovies($root, $args, $content, $info)
    {
        $limit = data_get($args, 'limit', 7);
        if ($user = currentUser()) {
            //收藏过的电影类型
            $movies_ids = $user->favoritedMovie()->pluck('favorable_id')->toArray();
            $regions    = Movie::whereIn('id', $movies_ids)->pluck('region')->toArray();
            //推算喜欢的区域
            $movies = Movie::inRandomOrder()
                ->whereIn('region', $regions)
                ->take($limit)
                ->get();
            $moviesCount = count($movies);

            //推算区域不够数，随机补充？
            if ($moviesCount < $limit) {
                $random_movies = Movie::inRandomOrder()->take($limit - $moviesCount)->get();
                $movies        = array_merge($movies->toArray(), $random_movies->toArray());
            }
            return $movies;
        } else {
            return Movie::inRandomOrder()->take($limit)->get();
        }
    }

    public function resolveClipMovie($root, $args, $content, $info)
    {
        $user        = getUser();
        $start       = $args['startTime'];
        $end         = $args['endTime'];
        $postTitle   = $args['postTitle'];
        $movie_id    = $args['movie_id'];
        $m3u8        = $args['targetM3u8'];
        $seriesIndex = $args['seriesIndex'];
        $playLine    = $args['playLine'];

        $movie      = Movie::find($movie_id);
        $seriesName = $seriesName ?? $movie->findSeriesName($playLine, $seriesIndex);
        $video      = MovieRepo::storeClipMovieByApi($user, $movie, $m3u8, $start, $end, $postTitle, $seriesName);
        $post       = $video->post;

        //movie计数剪辑数count_clip
        $movie->count_clips = $movie->videos()->count();
        $movie->save();
        return $post;

    }

    public function resolveCategoryMovie($root, $args, $content, $info)
    {

        request()->request->add(['fetch_sns_detail' => true]);

        $region = data_get($args, 'region');
        //类型
        $type = data_get($args, 'type');
        //风格
        $style = data_get($args, 'style');
        //国家
        $country = data_get($args, 'country');
        //语言
        $lang = data_get($args, 'lang');
        //年份
        $year = data_get($args, 'year');
        //排序规则
        $scopes = data_get($args, 'scopes');

        $query = Movie::when($region && $region != 'ALL', function ($qb) use ($region, $scopes) {
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
        return $query;
    }

    public function resolveMovie($root, $args, $content, $info)
    {
        //标记获取详情数据信息模式
        request()->request->add(['fetch_sns_detail' => true]);

        $movie_id = data_get($args, 'movie_id');
        // app_track_event('长视频', '电影详情', $movie_id);

        $movie = Movie::withoutGlobalScopes()->find($movie_id);
        if (isset($movie)) {
            $movie->hits = $movie->hits + 1;
            $movie->saveQuietly();
        }
        //影片详情页真实返回影片信息和状态
        return $movie;
    }

    public function explainMovieList($root, $args, $content, $info)
    {
        return Movie::where('type_name', '电影解说')->latest('rank')->latest('hits');
    }

    public function userViewingHistory($root, $args, $content, $info)
    {
        $user = getUser();
    }

    /**
     * 关联电影到动态
     */
    public function resolveHookMovie($root, array $args, $context, $resolveInfo)
    {
        $movie = Movie::withoutGlobalScopes()->find($args['movie_id']);
        if ($movie) {
            $post = Post::find($args['post_id']);
            optional($post)->update(['movie_id' => $movie->id]);

            //相同用户对同一个电影的关联动态，自动组合成一个动态合集,动态投稿到专题
            if ($video = $post->video) {
                // 剪辑的视频和movie的关系才是稳定的， 粘贴先不确定影片关系
                // $video->update(['movie_id' => $movie->id]);
                $video->autoHookMovieCollection($post, $movie, '解说');
                $video->autoHookMovieCategory($post, $movie);
            }

            //返回合集，专题信息关联好的post
            return $post;
        }
    }

    public function movieRelationPost($root, $args, $content, $info)
    {
        $id    = $args['movie_id'];
        $movie = Movie::find($id);
        return Post::where('description', 'like', "%$movie->name%")->take(10)->get();
    }

    /**
     * 搜索的影片(仅公开片源)
     */
    public function resolveSearchMovies($root, $args, $content, $info)
    {
        $keyword = data_get($args, 'keyword');
        app_track_event('长视频', '搜索电影', $keyword);

        //标记获取详情数据信息模式
        request()->request->add(['fetch_sns_detail' => true]);

        //记录搜索历史
        // 保存搜索记录
        $log = SearchLog::firstOrNew([
            'keyword' => $keyword,
        ]);
        // 如果有完全匹配的作品名字
        if ($movie = Movie::publish()->where('name', $keyword)->orderBy('id')->first()) {
            $log->movie_type   = $movie->type_name;
            $log->movie_reigon = $movie->country;
            //记录用户，作为展示的历史搜索数据
            if ($user = currentUser()) {
                $log->user_id = $user->id;
            }
            if (isset($log->id)) {
                $log->increment('count');
            }
        }
        $log->save();

        return static::publish()->search($keyword);
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
                ['全部', '韩剧', '日剧', '美剧'],
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
            if (currentUser()) {
                if ($qb->count() > 0) {
                    Dimension::track("长视频搜索成功数", 1, "长视频");
                }
                $log = SearchLog::saveSearchLog($keyword, getUserId());
            }
        }

        return $qb;
    }

    // 韩剧星球，高甜榜单接口
    public function sweetyRankList($root, array $args, $context, $resolveInfo)
    {
        return Movie::hanju()->latest('rank')->latest('hits');
    }

    // 全部影视
    public function movieList($root, array $args, $context, $resolveInfo)
    {
        return Movie::latest('rank')->latest('hits');
    }

    public function getSharePciture($root, array $args, $context, $resolveInfo)
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

    //剪辑的UGC的专题
    public function resolveUgcCategory($root, array $args, $context, $resolveInfo)
    {
        $movie = $root;
        return Category::whereName($movie->name)->whereType('movie')->first();
    }

    // 获取影片相关的剪辑
    public function resolveClips($root, array $args, $context, $resolveInfo)
    {
        $top = $args['top'] ?? 3;
        return DB::table('posts')->join('videos', 'videos.id', '=', 'posts.video_id')
            ->where('posts.movie_id', $this->id)->whereNotNull('videos.movie_id')
            ->latest('posts.id')->take($top)->get();
    }

    // 获取影片相关的解说
    public function resolveJieShuo($root, array $args, $context, $resolveInfo)
    {
        $top = $args['top'] ?? 3;
        return DB::table('posts')->join('videos', 'videos.id', '=', 'posts.video_id')
            ->where('posts.movie_id', $this->id)->whereNull('videos.movie_id')
            ->latest('posts.id')->take($top)->get();
    }

}
