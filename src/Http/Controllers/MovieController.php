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

        $category = data_get(Movie::getCategories(), $categoryId);

        $query = \App\Movie::where('status', '!=', Movie::DISABLED)
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
        $query  = request()->get('q');
        $result = Movie::orderBy('id')->where('name', 'like', '%' . $query . '%')->paginate(10);
        $result->appends(['q' => $query]);
        $hot       = Movie::orderBy('id')->paginate(10);
        $recommend = Movie::enable()->where('rank', 30)->inRandomOrder()->take(4)->get();
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
        $qb      = Movie::enable()->where('category_id', $id)->latest('rank');
        $orderBy = 'like_count';
        if ($order = request()->get('order')) {
            $qb->latest($order);
            $orderBy = $order;
        }
        $data = [
            'cate'        => Movie::getCategories()[$id],
            'movies'      => $qb->paginate(30),
            'category_id' => $id,
            'orderBy'     => $orderBy,
        ];

        return view('movie.category', $data);
    }

    /**
     * 首页数据的全部逻辑
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $qb             = Movie::latest('id')->where('status', '!=', Movie::DISABLED);
        $hotMovies      = (clone $qb)->take(15)->get();
        $categoryMovies = [
            '热门美剧' => [
                (clone $qb)->where('region', '美剧')->take(6)->get(),
                (clone $qb)->where('region', '美剧')->take(12)->get(),
                'meiju',
            ],
            '热门日剧' => [
                (clone $qb)->where('region', '日剧')->latest('id')->take(6)->get(),
                (clone $qb)->where('region', '日剧')->latest('id')->take(12)->get(),
                'riju',
            ],
            '热门韩剧' => [
                (clone $qb)->where('region', '韩剧')->latest('id')->take(6)->get(),
                (clone $qb)->where('region', '韩剧')->take(12)->get(),
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
                'movies' => (clone $qb)->where('region', '美剧')->offset(18)->take(8)->get(),
            ],
            '日剧' => [
                'cate'   => 'riju',
                'movies' => (clone $qb)->where('region', '日剧')->offset(36)->take(8)->get(),
            ],
            '韩剧' => [
                'cate'   => 'hanju',
                'movies' => (clone $qb)->where('region', '韩剧')->offset(18)->take(8)->get(),
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
            $qb = Movie::orderByDesc($order);
        } else {
            $qb = Movie::orderBy('id');
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
            $qb = Movie::orderByDesc($order);
        } else {
            $qb = Movie::orderBy('id');
        }
        $movies = (clone $qb)->where('region', "美剧")->paginate(24);
        return view('movie.region')->with('movies', $movies)->withCate("美剧")->with('cate_id', 2);
    }

    public function hanju()
    {
        $order = request()->get('order');
        if ($order) {
            $qb = Movie::orderByDesc($order);
        } else {
            $qb = Movie::orderBy('id');
        }
        $movies = (clone $qb)->where('region', "韩剧")->paginate(24);
        return view('movie.region')->with('movies', $movies)->withCate("韩剧")->with('cate_id', 3);
    }

    public function gangju()
    {
        $order = request()->get('order');
        if ($order) {
            $qb = Movie::orderByDesc($order);
        } else {
            $qb = Movie::orderBy('id');
        }
        $movies = (clone $qb)->where('region', "港剧")->paginate(24);
        return view('movie.region')->with('movies', $movies)->withCate("港剧")->with('cate_id', 4);
    }

    public function show(Movie $movie)
    {
        $movie->hits = $movie->hits + 1;
        $movie->save();
        $qb   = Movie::latest('updated_at');
        $more = $qb->take(6)->get();
        //FIXME: 用sns 实现是否已收藏...
        $movie->favorited = false;
        $recommend        = Movie::enable()->latest('rank')->inRandomOrder()->take(6)->get();
        $more             = Movie::enable()->latest('rank')->inRandomOrder()->take(6)->get();

        // 兼容内涵电影vue用的series属性
        $movie->series = $movie->data;
        $movie->data   = null;

        return view('movie.show')->with('movie', $movie)->with('recommend', $recommend)
            ->with('more', $more);
    }

    public function favorites()
    {
        $user = \Auth::user();
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

        $movies = Movie::whereIn('id', $movieID)->paginate(18);
        return view('movie.favorites', [
            'cate'   => $cate,
            'movies' => $movies,
        ]);
    }
}
