<?php

namespace Haxibiao\Media\Http\Api;

use App\Http\Controllers\Controller;
use App\Like;
use App\Movie;
use App\MovieHistory;
use Illuminate\Support\Facades\Auth;

class MovieController extends Controller
{
    /**
     * 返回剧集数据
     * @deprecated 新版vue目前直接blade获得movie对象包含series data
     * @param int $id
     * @return array
     */
    public function getSeries($id)
    {
        $movie  = Movie::findOrFail($id);
        $result = [];

        //开始同步电影模块的站
        if (config('media.movie.enable')) {
            return $movie->data;
        }

        $series = $movie->series;
        foreach ($series as $item) {
            $result[] = [
                'id'   => $item->id,
                'url'  => $item->play_url,
                'name' => $item->name,
            ];
        }
        return $result;
    }

    public function movieHistory()
    {
        $user = Auth::user();
        return [
            'data'        => MovieHistory::where('user_id', $user->id)->get()->toArray(),
            'message'     => '获取浏览记录成功',
            'status_code' => 200,
        ];
    }

    // public function comment()
    // {
    //     $user     = \Auth::user();
    //     $content  = request()->get('content');
    //     $movie_id = request()->get('movie_id');
    //     $comment  = Comment::with('user')->create([
    //         'user_id'          => $user->id,
    //         'content'          => $content,
    //         'commentable_id'   => $movie_id,
    //         'commentable_type' => 'movies',
    //     ]);
    //     // 兼容前端结构，并且需要多包一个 collect
    //     $comment = Comment::find($comment->id);
    //     return returnData(collect($comment->toArray()), '发布评论成功', 200);
    // }

    // public function getComment($id)
    // {
    //     $movie = Movie::find($id);
    //     $page  = request()->get('page');
    //     $order = request()->get('order', 'like_count');
    //     $qb    = Comment::morphQuery('movies', $movie->id)->latest($order)->with('user')->take(10);
    //     if ($page && $page > 1) {
    //         $qb->offset($page * 10);
    //     }
    //     return returnData($qb->get()->toArray(), '获取评论成功', 200);
    // }

    //FIXME: 需要把sns当做base一样的基础包依赖，重构通用sns功能
    public function toggoleLike()
    {
        if (checkUser()) {
            $user     = getUser();
            $movie_id = request()->get('movie_id');
            $type     = request()->get('type');
            $like     = Like::firstOrNew([
                'user_id'      => $user->id,
                'likable_id'   => $movie_id,
                'likable_type' => $type,
            ]);
            if ($like->id) {
                $like->delete();
                $isLike = false;
            } else {
                $like->save();
                $isLike = true;
            }
            return [
                'data'        => $isLike,
                'message'     => '点赞操作成功',
                'status_code' => 200,
            ];
        }
    }

    //FIXME: 需要把sns当做base一样的基础包依赖，重构通用sns功能
    public function toggoleFan()
    {
        if (checkUser()) {
            $user     = getUser();
            $movie_id = request()->get('movie_id');
            $fan      = \App\Favorite::firstOrNew([
                'user_id'    => $user->id,
                'faved_id'   => $movie_id,
                'faved_type' => 'movies',
            ]);
            if ($fan->id) {
                $fan->delete();
                $isfan = false;
            } else {
                $fan->save();
                $isfan = true;
            }
            return [
                'data'        => $isfan,
                'message'     => '收藏操作成功',
                'status_code' => 200,
            ];
        }
    }

    public function saveWatchProgress()
    {
        $user     = Auth::user();
        $movieid  = request()->get('movie_id');
        $seriesid = request()->get('series_id');
        $progress = request()->get('progress');
        // 保存观看历史
        MovieHistory::updateOrCreate([
            'user_id'  => $user->id,
            'movie_id' => $movieid,
        ], [
            'series_id' => $seriesid,
            'progress'  => $progress,
        ]);
        return returnData(true, '保存观影数据成功', 200);
    }
}
