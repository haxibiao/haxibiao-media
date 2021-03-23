<?php

namespace Haxibiao\Media\Http\Api;

use App\Comment;
use App\Http\Controllers\Controller;
use App\Like;
use App\Movie;
use App\MovieHistory;
use App\Report;
use App\User;
use Haxibiao\Media\Danmu;
use Haxibiao\Media\Events\DanmuEvent;
use Haxibiao\Media\Traits\MovieRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MovieController extends Controller
{

    public function getComment($id)
    {
        $movie = Movie::find($id);
        $page  = request()->get('page');
        // $order = request()->get('order', 'id');
        $qb = Comment::where([
            'commentable_id'   => $movie->id,
            'commentable_type' => 'movies',
        ])->latest('id')->with('user')->take(10);
        if ($page && $page > 1) {
            $qb->offset($page * 10);
        }
        return returnData($qb->get()->toArray(), '获取评论成功', 200);
    }

    public function comment()
    {
        $user     = getUser();
        $content  = request()->get('content');
        $movie_id = request()->get('movie_id');
        $comment  = Comment::create([
            'user_id'          => $user->id,
            'body'             => $content,
            'commentable_id'   => $movie_id,
            'commentable_type' => 'movies',
        ]);
        // 兼容前端结构，并且需要多包一个 collect
        $comment = Comment::find($comment->id);
        return returnData(collect($comment->toArray()), '发布评论成功', 200);
    }

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

        //同步电影模块的站 用 data json读取剧集播放
        if (config('media.movie.enable')) {
            return $movie->series_urls;
        }

        //内涵电影，用源series
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

    public function clip(Request $request)
    {
        $user       = getUser();
        $start      = $request->get('start_time');
        $end        = $request->get('end_time');
        $postTitle  = $request->get('post_title');
        $movie_id   = $request->get('movie_id');
        $m3u8       = $request->get('m3u8');
        $movie      = Movie::find($movie_id);
        $seriesName = MovieRepo::findSeriesName($m3u8, $movie);
        $newM3u8    = MovieRepo::ClipMovie($m3u8, $start, $end);
        $post       = MovieRepo::storeClipMovie($user, $movie, $newM3u8, $postTitle, $seriesName);
        return returnData($post->toArray(), '剪辑成功', 200);
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

    public function report()
    {
        if ($user = checkUser()) {
            $id     = request()->get('id');
            $remark = request()->get('remark');
            $report = Report::create([
                'reportable_type' => 'movies',
                'reportable_id'   => $id,
                'reason'          => $remark,
                'user_id'         => optional($user)->id,
            ]);
            return returnData($report, '举报成功', 200);
        }
    }

    //FIXME: 需要把sns当做base一样的基础包依赖，重构通用sns功能
    public function toggoleFan()
    {
        if (checkUser()) {
            $user     = getUser();
            $movie_id = request()->get('movie_id');
            $fan      = \App\Favorite::firstOrNew([
                'user_id'        => $user->id,
                'favorable_id'   => $movie_id,
                'favorable_type' => 'movies',
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

/**
 * 发送弹幕
 */
    public function sendDanmu()
    {
        //发送弹幕用户 此处如果前端不传递 默认是DIYgod 正确的参数应该是user id
        $author = request()->get('author');

        if ($author != "DIYgod") {
            //弹幕颜色
            $color = request()->get('color') ?? 16777215;
            //弹幕内容
            $text = request()->get('text');
            //发送弹幕时间
            $time = request()->get('time') ?? "1";
            //发送弹幕类型
            $type = request()->get('type');

            //series index；
            $id           = request()->get('id');
            $array        = explode('_', $id);
            $movie_id     = $array[0];
            $series_index = $array[1];
            $series_id    = Movie::query()->find($movie_id)->series->get($series_index - 1)->id;

            $danmu = Danmu::create([
                'user_id'   => $author,
                'movie_id'  => $movie_id,
                'series_id' => $series_id,
                'content'   => $text,
                'time'      => $time,
            ]);
            $result = [
                'code' => 0,
                'data' => [],
            ];

            //实时弹幕
            broadcast(new DanmuEvent($danmu, $movie_id, $series_index));

            $result['data'] = $danmu->toArray();

            return $result;
        }

        //如果code 不为0 前端会报错
        return
            [
            'code' => 0,
            'data' => [],
        ];
    }

    /**
     * 返回弹幕
     */
    public function danmu()
    {
        $id           = request()->get('id');
        $array        = explode('_', $id);
        $movie_id     = $array[0];
        $series_index = $array[1] - 1;
        $series_id    = Movie::query()->find($movie_id)->series->get($series_index)->id;
        $result       = [
            'code' => 0,
            'data' => [],
        ];
        $comments = Danmu::query()->where('series_id', $series_id)->get();
        foreach ($comments as $key => $comment) {
            $temp[] = $comment->time;
            $temp[] = $comment->type;
            $temp[] = $comment->color;
            $temp[] = "弹幕小子";
            $temp[] = $comment->content;

            $result['data'][$key] = $temp;
            $temp                 = [];
        }
        return $result;
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
