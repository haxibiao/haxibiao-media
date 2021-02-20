<?php

namespace Haxibiao\Media\Http\Controllers;

use App\Favorite;
use App\Like;
use App\Movie;
use App\MovieHistory;
use Haxibiao\Media\Http\Controller;
use Haxibiao\Media\SearchLog;

class MovieController extends Controller
{
    public function search()
    {
        $query  = request()->get('q');
        $result = Movie::orderBy('id')->where('name', 'like', '%' . $query . '%')->paginate(10);
        $result->appends(['q' => $query]);
        $hot       = Movie::orderBy('id')->paginate(10);
        $recommend = Movie::enable()->where('rank', 30)->inRandomOrder()->take(4)->get();
        // 保存搜索记录
        $log = SearchLog::firstOrNew([
            'keyword' => $query,
        ]);
        // 如果有完全匹配的作品名字
        if ($movie = Movie::where('name', $query)->orderBy('id')->first()) {
            $log->movie_type   = $movie->type_name;
            $log->movie_reigon = $movie->country;
            if (isset($log->id)) {
                $log->increment('count');
            }
        }
        $log->save();

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
            '热门美剧'  => [
                (clone $qb)->where('region', '美剧')->take(6)->get(),
                (clone $qb)->where('region', '美剧')->take(12)->get(),
                'meiju',
            ],
            '热门日剧'  => [
                (clone $qb)->where('region', '日剧')->latest('id')->take(6)->get(),
                (clone $qb)->where('region', '日剧')->latest('id')->take(12)->get(),
                'riju',
            ],
            '热门韩剧'  => [
                (clone $qb)->where('region', '韩剧')->latest('id')->take(6)->get(),
                (clone $qb)->where('region', '韩剧')->take(12)->get(),
                'hanju',
            ],
            '怀旧老港剧' => [
                (clone $qb)->where('region', '港剧')->latest('id')->take(6)->get(),
                (clone $qb)->where('region', '港剧')->latest('id')->take(12)->get(),
                'gangju',
            ],
        ];
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
            '港剧' => [
                'cate'   => 'gangju',
                'movies' => (clone $qb)->where('region', '港剧')->offset(18)->take(8)->get(),
            ],
        ];
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
        if ($user = getUser(false)) {
            //记录观看位置
            MovieHistory::updateOrCreate([
                'user_id'  => $user->id,
                'movie_id' => $movie->id,
            ], [
                'last_watch_time' => now(),
            ]);
            //收藏状态
            $movie->favorited = false;
            //喜欢状态
            //FIXME: 用sns里的traits实现

        }
        //FIXME: 用sns里的traits实现
        $recommend = Movie::enable()->latest('rank')->inRandomOrder()->take(6)->get();
        $more      = Movie::enable()->latest('rank')->inRandomOrder()->take(6)->get();
        //加载剧集
        $movie->load('series');
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
