<?php

namespace Haxibiao\Media\Http\Controllers;

use App\Favorite;
use App\Like;
use App\Movie;
use App\MovieHistory;
use App\User;
use Haxibiao\Media\Http\Controller;
use Haxibiao\Media\SearchLog;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    // movie/list/{分类}-{年代}-{类型}-{地区}-{语言}-{排序}----
    // 预留三个参数
    public function movies($pattern)
    {
        $parameters = explode('-', $pattern);

        $categoryId = data_get($parameters, '0'); // region
        $year       = data_get($parameters, '1'); // year
        $type       = data_get($parameters, '2'); // type_name
        $area       = data_get($parameters, '3'); // country
        $language   = data_get($parameters, '4'); // lang
        $order      = data_get($parameters, '5', 'latest'); // order

        $category = data_get(Movie::publish()->getCategories(), $categoryId);

        //原来 这里是status!=-1显示数据，还有一些项目因为历史原因status=-2，-6...
        //这里换成publish会导致一些web首页movie数据出不来，注意去修改movie数据status为1
        $query = \App\Movie::publish()
            ->when($category, function ($q) use ($category) {
                return $q->where('region', $category);
            })->when($year, function ($q) use ($year) {
            $years = explode('_', $year);
            if (count($years) > 2) {
                return $q->whereBetWeen('year', [data_get($years, '1'), data_get($years, '0')]);
            }
            return $q->where('year', $year);
        })->when($type, function ($q) use ($type) {
            return $q->where('type_name', $type);
        })->when($area, function ($q) use ($area) {
            return $q->where('country', $area);
        })->when($language, function ($q) use ($language) {
            return $q->where('lang', $language);
        });
        // TODO tracker
        //        if($order === 'latest'){
        $query = $query->orderBy('id', 'desc');
//        } elseif ($order === 'hot'){
        //            $query = $query->orderBy('rank', 'desc');
        //        }
        $movies = $query->paginate(40);
        return view('movie.region')->with('movies', $movies);
    }

    public function search()
    {
        $query = request()->get('q');
        if (config('media.meilisearch.enable', false)) {
            $result = Movie::search($query)->paginate(10);
        } else {
            $result = Movie::publish()->orderBy('id')->where('name', 'like', '%' . $query . '%')->paginate(10);
        }
        $result->appends(['q' => $query]);
        $hot       = Movie::publish()->orderBy('id')->paginate(10);
        $recommend = Movie::publish()->where('rank', 30)->take(4)->inRandomOrder()->get();
        SearchLog::saveSearchLog($query);
        return view('movie.search', [
            'hot'          => $hot,
            'result'       => $result,
            'recommend'    => $recommend,
            'queryKeyword' => $query,
        ]);
    }

    public function category($id)
    {
        //此电影分类已不用，但还是有地方在调此路由，故做一下跳转
        $order  = request()->get('order');
        $method = 'index';
        switch ($id) {
            case 1:
                $method = 'riju';
                break;
            case 2:
                $method = 'meiju';
                break;
            case 3:
                $method = 'hanju';
                break;
            case 4:
                $method = 'gangju';
                break;
        }
        if ($order) {
            return redirect()->action('\Haxibiao\Media\Http\Controllers\MovieController@' . $method, ['order' => $order]);
        } else {
            return redirect()->action('\Haxibiao\Media\Http\Controllers\MovieController@' . $method);
        }

        // $qb      = Movie::enable()->where('category_id', $id)->latest('rank');
        // $orderBy = 'like_count';
        // if ($order = request()->get('order')) {
        //     $qb->latest($order);
        //     $orderBy = $order;
        // }
        // $data = [
        //     'cate'        => Movie::getCategories()[$id],
        //     'movies'      => $qb->paginate(30),
        //     'category_id' => $id,
        //     'orderBy'     => $orderBy,
        // ];
        // return view('movie.category', $data);
    }

    /**
     * 首页数据的全部逻辑
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {

        $qb             = Movie::publish();
        $hotMovies      = (clone $qb)->take(15)->get();
        $categoryMovies = [
            '热门美剧' => [
                (clone $qb)->where('region', '美剧')->orderByDesc('updated_at')->take(6)->get(),
                (clone $qb)->where('region', '美剧')->orderByDesc('updated_at')->take(12)->get(),
                'meiju',
            ],
            '热门日剧' => [
                (clone $qb)->where('region', '日剧')->orderByDesc('updated_at')->take(6)->get(),
                (clone $qb)->where('region', '日剧')->orderByDesc('updated_at')->take(12)->get(),
                'riju',
            ],
            '热门韩剧' => [
                (clone $qb)->where('region', '韩剧')->orderByDesc('updated_at')->take(6)->get(),
                (clone $qb)->where('region', '韩剧')->orderByDesc('updated_at')->take(12)->get(),
                'hanju',
            ],
        ];
//        注释的原因：凡是我们自己的域名先隐藏中国境内的影片，目前在诉讼期间，对方正在搜集我们的证据。
        //        if(is_null(data_get(app('cms_site'),'company',null))){
        //            $categoryMovies = array_merge($categoryMovies,[
        //                '怀旧老港剧' => [
        //                    (clone $qb)->where('region', '港剧')->latest('id')->take(6)->get(),
        //                    (clone $qb)->where('region', '港剧')->latest('id')->take(12)->get(),
        //                    'gangju',
        //                ]
        //            ]);
        //        }
        $cate_ranks = [
            '美剧' => [
                'cate'   => 'meiju',
                'movies' => (clone $qb)->where('region', '美剧')->orderByDesc('updated_at')->offset(18)->take(8)->get(),
            ],
            '日剧' => [
                'cate'   => 'riju',
                'movies' => (clone $qb)->where('region', '日剧')->orderByDesc('updated_at')->offset(36)->take(8)->get(),
            ],
            '韩剧' => [
                'cate'   => 'hanju',
                'movies' => (clone $qb)->where('region', '韩剧')->orderByDesc('updated_at')->offset(18)->take(8)->get(),
            ],
        ];
//        注释的原因：凡是我们自己的域名先隐藏中国境内的影片，目前在诉讼期间，对方正在搜集我们的证据。
        //        if(is_null(data_get(app('cms_site'),'company',null))){
        //            $cate_ranks = array_merge($cate_ranks,[
        //                '港剧' => [
        //                    'cate'   => 'gangju',
        //                    'movies' => (clone $qb)->where('region', '港剧')->offset(18)->take(8)->get(),
        //                ],
        //            ]);
        //        }

        return view('movie.index', [
            'hotMovies'      => $hotMovies,
            'categoryMovies' => $categoryMovies,
            'cate_ranks'     => $cate_ranks,
        ]);
    }

    public function riju()
    {
        $order = request()->get('order');
        if ($order) {
            $qb = Movie::publish()->orderByDesc($order);
        } else {
            $qb = Movie::publish()->orderBy('id');
        }
        $movies = (clone $qb)->where('region', "日剧")->paginate(24);
        return view('movie.region')->with('movies', $movies)->withCate("日剧")->with('cate_id', 1);
    }

    public function login(Request $request)
    {
        $phone = $request->get('phone');
        $pwd   = $request->get('pwd');
        $name  = $request->get('name', '匿名用户');
        $user  = User::where('phone', $phone)->first();
        if ($user) {
            if (password_verify($pwd, $user->password) == false) {
                return returnData(null, '账号或密码不对', 403);
            }
        } else {
            // 首次登陆即注册
            $user = User::CreateUser($name, $phone, $pwd);
        }
        \Auth::login($user, true);
        return returnData($user->toArray(), '登录成功', 200);
    }

    public function meiju()
    {
        $order = request()->get('order');
        if ($order) {
            $qb = Movie::publish()->orderByDesc($order);
        } else {
            $qb = Movie::publish()->orderBy('id');
        }
        $movies = (clone $qb)->where('region', "美剧")->paginate(24);
        return view('movie.region')->with('movies', $movies)->withCate("美剧")->with('cate_id', 2);
    }

    public function hanju()
    {
        $order = request()->get('order');
        if ($order) {
            $qb = Movie::publish()->orderByDesc($order);
        } else {
            $qb = Movie::publish()->orderBy('id');
        }
        $movies = (clone $qb)->where('region', "韩剧")->paginate(24);
        return view('movie.region')->with('movies', $movies)->withCate("韩剧")->with('cate_id', 3);
    }

    public function gangju()
    {
        $order = request()->get('order');
        if ($order) {
            $qb = Movie::publish()->orderByDesc($order);
        } else {
            $qb = Movie::publish()->orderBy('id');
        }
        $movies = (clone $qb)->where('region', "港剧")->paginate(24);
        return view('movie.region')->with('movies', $movies)->withCate("港剧")->with('cate_id', 4);
    }

    public function show(Movie $movie)
    {

        $movieColumns = $movie->getTableColumns();
        if (in_array('hits', $movieColumns)) {
            $movie->hits = $movie->hits + 1;
            $movie->saveQuietly();
        }
        //FIXME: 用sns 实现是否已收藏...
        $movie->favorited = false;

        // 推荐同导演（性能考虑）
        $qb          = Movie::publish();
        $qb_producer = $qb->where('id', '<>', $movie->id);
        if (in_array('producer', $movieColumns)) {
            $qb_producer = $qb_producer->where('producer', $movie->producer);
        }
        $recommend = $qb_producer->take(6)->get()->shuffle();
        //更多同地区 （太多，不能随机排序）
        $qb         = Movie::publish()->latest('id');
        $qb_country = $qb->where('id', '<>', $movie->id);

        if (in_array('country', $movieColumns)) {
            $qb_country = $qb_country->where('country', $movie->country);
        }
        if (in_array('type', $movieColumns)) {
            $qb_country = $qb_country->where('type', $movie->type);
        }

        //去掉order by rank, 数据太多mysql error: SQLSTATE[HY001]: Memory allocation error: 1038
        $more = $qb_country->skip(rand(0, 500))->take(6)->get();

        if ($user = currentUser()) {
            // 保存观看历史
            MovieHistory::updateOrCreate([
                'user_id'  => $user->id,
                'movie_id' => $movie->id,
            ], [
                'last_watch_time' => now(),
            ]);
            $isLike = Like::where([
                'user_id'      => $user->id,
                'likable_type' => 'movies',
                'likable_id'   => $movie->id,
            ])->exists();
            $isBeMovieFan = Favorite::where([
                'user_id'        => $user->id,
                'favorable_type' => 'movies',
                'favorable_id'   => $movie->id,
            ])->exists();
            $movie->isliked = $isLike;
            $movie->isFan   = $isBeMovieFan;
        }
        return view('movie.show')->with('movie', $movie)->with('recommend', $recommend)
            ->with('more', $more);
    }

    public function favorites()
    {
        $user = getUser();
        $type = request()->get('type');

        if ($type == 'like') {
            $movieID = Like::where([
                'user_id'      => $user->id,
                'likable_type' => 'movies',
            ])->select('likable_id')->get()->pluck('likable_id');
            $cate = "喜欢";
        } else if ($type == 'favorite') {
            $movieID = Favorite::where([
                'user_id'        => $user->id,
                'favorable_type' => 'movies',
            ])->select('favorable_id')->get()->pluck('favorable_id');
            $cate = "收藏";
        } else if ($type == 'history') {
            $movieID = MovieHistory::where([
                'user_id' => $user->id,
            ])->select('movie_id')->get()->pluck('movie_id');
            $cate = "足迹";
        }

        $movies = Movie::publish()->withoutGlobalScopes()->whereIn('id', $movieID)->paginate(18);
        return view('movie.favorites', [
            'cate'   => $cate,
            'movies' => $movies,
        ]);
    }

    public function dongman()
    {
        $movies = Movie::where('custom_type', '动漫')->orderBy('rank', 'desc')->latest('updated_at')->paginate(24);
        return view('movie.region')->with('movies', $movies)->withCate("动漫")->with('cate_id', 1);
    }

    public function dianying()
    {
        $movies = Movie::where('custom_type', '电影')->orderBy('rank', 'desc')->latest('updated_at')->paginate(24);
        return view('movie.region')->with('movies', $movies)->withCate("电影")->with('cate_id', 1);
    }

    public function dianshiju()
    {
        $movies = Movie::where('custom_type', '电视剧')->orderBy('rank', 'desc')->latest('updated_at')->paginate(24);
        return view('movie.region')->with('movies', $movies)->withCate("电视剧")->with('cate_id', 1);
    }

    public function zongyi()
    {
        $movies = Movie::whereIn('custom_type', ['综艺', '真人秀'])->orderBy('rank', 'desc')->latest('updated_at')->paginate(24);
        return view('movie.region')->with('movies', $movies)->withCate("综艺")->with('cate_id', 1);
    }
}
